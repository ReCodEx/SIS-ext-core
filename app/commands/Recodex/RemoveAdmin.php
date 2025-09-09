<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use App\Model\Repository\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remove admin from a group.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
class RecodexRemoveAdmin extends BaseCommand
{
    protected static $defaultName = 'recodex:remove-admin';

    /**
     * @var RecodexApiHelper
     */
    private $recodexApi;

    /**
     * @var Users
     */
    private $users;

    /**
     * @param RecodexApiHelper $recodexApi
     * @param Users $users
     */
    public function __construct(RecodexApiHelper $recodexApi, Users $users)
    {
        parent::__construct();
        $this->recodexApi = $recodexApi;
        $this->users = $users;
    }

    /**
     * Register the command.
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)->setDescription('Remove admin from a group.');
        $this->addArgument(
            'groupId',
            InputArgument::REQUIRED,
            'ID of the group from which the admin will be removed.'
        );
        $this->addArgument('adminId', InputArgument::REQUIRED, 'ID of the admin to be removed.');
    }

    /**
     * @param InputInterface $input Console input, not used
     * @param OutputInterface $output Console output for logging
     * @return int 0 on success, 1 on error
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $groupId = $input->getArgument('groupId');
        $adminId = $input->getArgument('adminId');
        $admin = $this->users->findOrThrow($adminId);

        $token = trim($this->prompt('Auth token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }
        $this->recodexApi->setAuthToken($token);

        $output->writeln("Removing admin '$adminId' from group '$groupId'...");
        $this->recodexApi->removeAdminFromGroup($groupId, $admin);

        return Command::SUCCESS;
    }
}
