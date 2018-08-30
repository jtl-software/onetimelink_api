<?php
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 18/05/18
 */

namespace JTL\Onetimelink\CLI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteResetHashesCommand extends Command
{
    /**
     * @var string
     */
    private $userListPath;

    public function __construct(string $userListPath)
    {
        parent::__construct('reset-hashes:deleteoldhashes');
        $this->userListPath = $userListPath;
    }

    protected function configure()
    {
        $this->setName('reset-hashes:delete')
            ->setDescription('Delete password reset hashes for all users.')
            ->addOption(
                'time',
                't',
                InputOption::VALUE_REQUIRED,
                'After how many hours should password reset hashes be deleted (default: 24)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deleteAfterHours = $input->getOption('time') ?? 24;
        $deleteAfterDays = (int)$deleteAfterHours / 24;
        $userList = json_decode(file_get_contents($this->userListPath), true);
        $now = new \DateTimeImmutable();

        echo "The following users had their password reset hash deleted:\n";

        foreach ($userList['user'] as $email => $user) {
            if (isset($user['reset_hash'], $user['reset_hash_created'])) {
                $created = new \DateTimeImmutable($user['reset_hash_created']);
                $diff = $now->diff($created);

                if ($diff->h >= $deleteAfterHours || $diff->days >= $deleteAfterDays) {
                    echo "  - {$email}\n";
                    $userList['user'][$email]['reset_hash'] = null;
                    $userList['user'][$email]['reset_hash_created'] = null;
                }
            }
        }

        file_put_contents($this->userListPath, json_encode($userList, JSON_PRETTY_PRINT));
    }
}
