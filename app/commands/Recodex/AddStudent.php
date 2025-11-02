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
 * Add student into a group as a member.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
#[AsCommand(name: 'recodex:add-student', description: 'Add student into a group as a member.')]
class RecodexAddStudent extends BaseCommand
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
        $this->addArgument('groupId', InputArgument::REQUIRED, 'ID of the group to which the student will be added.');
        $this->addArgument('studentId', InputArgument::REQUIRED, 'ID of the student to be added.');
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
        $studentId = $input->getArgument('studentId');
        $student = $this->users->findOrThrow($studentId);

        $token = trim($this->prompt('Auth token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }
        $this->recodexApi->setAuthToken($token);

        $output->writeln("Adding student '$studentId' to group '$groupId'...");
        $this->recodexApi->addStudentToGroup($groupId, $student);

        return Command::SUCCESS;
    }
}
