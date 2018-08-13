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
 * Deletes all zip files that are older than 24 hours.
 * Deletes every link from history that is older than 30 days
 * Deletes every attachment that is older than 7 days or is associated with
 *   a link that has been deleted already
 * Deletes attachments that are not referenced in the database
 *
 * @package JTL\Onetimelink\CLI\Command
 */
class GarbageCollectCommand extends Command
{
    /** @var int */
    private $zipExpirationHours = 24;

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
                'zip_expiration',
                'z',
                InputOption::VALUE_REQUIRED,
                'After how many hours should temporary zip files be deleted (default: 24)'
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
        $zipExpirationHours = $input->getOption('zip_expiration');
        $linkExpirationDays = $input->getOption('link_expiration');
        $dataExpirationDays = $input->getOption('data_expiration');

        if ($zipExpirationHours !== null) {
            $this->zipExpirationHours = $zipExpirationHours;
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

        $output->writeln("Delete temp Download Files: zip_expiration time is {$this->zipExpirationHours} hours");
        $this->deleteTempDownloadFiles($this->downloadsPath, $output);
        $output->writeln("");

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
            $oldTime->format('Y-m-d H:i:s')
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
    private function deleteTempDownloadFiles(string $path, OutputInterface $output)
    {
        foreach (glob($path . '/*.zip') as $zipFile) {
            $stat = stat($zipFile);
            $mtime = (new \DateTimeImmutable())->setTimestamp($stat['mtime']);
            $diff = (new \DateTime())->diff($mtime);
            $hoursDiff = $diff->h;
            $hoursDiff += $diff->days * 24;

            if ($hoursDiff > $this->zipExpirationHours) {
                if (file_exists($zipFile)) {
                    $output->writeln("[{$hoursDiff} > {$this->zipExpirationHours}] deleting temporary ZIP file {$zipFile}");
                    unlink($zipFile);
                }
            }
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

            // All links associated with the attachment were deleted so it's safe to delete the attachment as well
            if (\count($links) === $deletedLinks) {
                if ($attachment->name === '-#-TEXTINPUT-#-') {
                    R::trash($attachment);
                } else {
                    if (file_exists($dataFile)) {
                        $output->writeln('Deleting temporary data file ' . $dataFile);
                        unlink($dataFile);
                        R::trash($attachment);
                    }
                }
            } else {
                // Otherwise delete data files older than 7 days
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
        }

        // Delete files that aren't referenced in the database
        foreach (glob($path . '/??/*') as $file) {
            $hash = basename($file);

            $attachment = R::findOne('attachment', 'hash = ?', [$hash]);

            if ($attachment === null && file_exists($file)) {
                $output->writeln('Deleting dead temporary data file ' . $dataFile);
                unlink($file);
            }
        }
    }
}