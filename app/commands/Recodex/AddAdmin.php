<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use App\Model\Repository\Users;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add admin into a group as a member.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
#[AsCommand(name: 'recodex:add-admin', description: 'Add admin into a group as a member.')]
class RecodexAddAdmin extends BaseCommand
{
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
        $this->addArgument('groupId', InputArgument::REQUIRED, 'ID of the group to which the admin will be added.');
        $this->addArgument('adminId', InputArgument::REQUIRED, 'ID of the admin to be added.');
    }

    /**
     * @param InputInterface $input Console input, not used
     * @param OutputInterface $output Console output for logging
     * @return int 0 on success, 1 on error
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
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

        $output->writeln("Adding admin '$adminId' to group '$groupId'...");
        $this->recodexApi->addAdminToGroup($groupId, $admin);

        return Command::SUCCESS;
    }
}
