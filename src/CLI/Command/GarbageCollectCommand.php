<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 23/04/18
 */

namespace JTL\Onetimelink\CLI\Command;

use RedBeanPHP\OODBBean;
use RedBeanPHP\R;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GarbageCollectCommand
 *
 * Deletes every link from history that is older than 30 days
 * Deletes every attachment that is older than 7 days or is associated with
 *   a link that has been deleted already
 * Deletes attachments that are not referenced in the database
 * Deletes upload tokens older than 7 days
 *
 * @package JTL\Onetimelink\CLI\Command
 */
class GarbageCollectCommand extends Command
{
    /** @var int */
    private $uploadTokenExpirationDays = 7;

    /** @var int */
    private $linkExpirationDays = 7;

    /** @var int */
    private $dataExpirationDays = 30;

    /** @var string */
    private $dataPath;

    /** @var string */
    private $downloadsPath;

    public function __construct(string $dataPath, string $downloadsPath)
    {
        parent::__construct();

        $this->dataPath = $dataPath;
        $this->downloadsPath = $downloadsPath;
    }

    /**
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('gc:run')
            ->setDescription('Runs the garbage collector.')
            ->addOption(
                'upload_expiration',
                'u',
                InputOption::VALUE_REQUIRED,
                'After how many days should upload tokens be invalidated (default: 7)'
            )
            ->addOption(
                'link_expiration',
                'l',
                InputOption::VALUE_REQUIRED,
                'After how many days should links be deleted from history (default: 7)'
            )
            ->addOption(
                'data_expiration',
                'd',
                InputOption::VALUE_REQUIRED,
                'After how many days should attachments and existing valid links be deleted (default: 30)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \RuntimeException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $uploadExpirationHours = $input->getOption('upload_expiration');
        $linkExpirationDays = $input->getOption('link_expiration');
        $dataExpirationDays = $input->getOption('data_expiration');

        if ($uploadExpirationHours !== null) {
            $this->uploadTokenExpirationDays = $uploadExpirationHours;
        }

        if ($linkExpirationDays !== null) {
            $this->linkExpirationDays = $linkExpirationDays;
        }

        if ($dataExpirationDays !== null) {
            $this->dataExpirationDays = $dataExpirationDays;
        }

        $now = new \DateTimeImmutable();
        $now = $now->format('c');
        $output->writeln("#### {$now} - Running Garbage Collection");

        $output->writeln("Delete Data Files: link_expiration time is {$this->linkExpirationDays} days");
        $this->deleteOldLinks($output);
        $output->writeln("");

        $output->writeln("data_expiration time is {$this->dataExpirationDays} days");
        $this->deleteDataFiles($this->dataPath, $output);
        $output->writeln("");

        $output->writeln("#### Process finished");
    }

    /**
     * @param OutputInterface $output
     * @throws \Exception
     */
    private function deleteOldLinks(OutputInterface $output)
    {
        $oldTime = new \DateTimeImmutable("{$this->linkExpirationDays} days ago");
        $oldLinks = R::findAll('link', 'created < ?', [
            $oldTime->format('Y-m-d H:i:s'),
        ]);

        if (\count($oldLinks) > 0) {
            $output->writeln(
                "Deleting the following links that are older than {$this->linkExpirationDays} days"
            );

            foreach ($oldLinks as $oldLink) {
                $output->writeln($oldLink->hash);
            }

            R::trashAll($oldLinks);
        }
    }

    /**
     * @param string $path
     * @param OutputInterface $output
     * @throws \Exception
     */
    private function deleteDataFiles(string $path, OutputInterface $output)
    {
        $attachments = R::findAll('attachment');
        $uploads = R::findAll('upload');

        /** @var OODBBean $upload */
        foreach ($uploads as $upload) {
            $createdAt = new \DateTimeImmutable($upload->created);
            $diff = (new \DateTime())->diff($createdAt);

            if ($diff->days > $this->uploadTokenExpirationDays) {
                $output->writeln('Deleting upload token ' . $upload->token);
                R::trash($upload);
            }
        }

        /** @var OODBBean $attachment */
        foreach ($attachments as $attachment) {
            $hash = $attachment->hash;
            $shortHash = substr($hash, 0, 2);
            $dataFile = $path . '/' . $shortHash . '/' . $hash;
            $links = $attachment->sharedLinkList;
            $deletedLinks = 0;

            foreach ($links as $link) {
                if ($link->deleted) {
                    ++$deletedLinks;
                }
            }

            $ctime = new \DateTimeImmutable($attachment->created);
            $diff = (new \DateTime())->diff($ctime);

            if ($diff->days > $this->dataExpirationDays) {
                if ($attachment->name === '-#-TEXTINPUT-#-') {
                    R::trash($attachment);
                } else {
                    if (file_exists($dataFile)) {
                        $output->writeln('Deleting temporary data file ' . $dataFile);
                        unlink($dataFile);
                        R::trash($attachment);

                        foreach ($links as $link) {
                            $link->deleted = (new \DateTimeImmutable())->format('Y-m-d  H:i:s');
                            R::store($link);
                            $output->writeln('Marking link ' . $link->hash . ' as deleted.');
                        }
                    }
                }
            }
        }

        // Delete files that aren't referenced in the database
        foreach (glob($path . '/??/*') as $file) {
            $hash = explode('_', basename($file))[0];

            $attachment = R::findOne('attachment', 'hash = ?', [$hash]);

            if ($attachment === null && file_exists($file)) {
                $output->writeln('Deleting dead temporary data file ' . $dataFile);
                unlink($file);
            }
        }
    }
}
