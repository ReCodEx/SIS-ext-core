<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use App\Model\Repository\SisScheduleEvents;
use App\Model\Repository\Users;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create a new group in ReCodEx form selected SIS event.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
class RecodexCreateGroup extends BaseCommand
{
    protected static $defaultName = 'recodex:create-group';

    /**
     * @var RecodexApiHelper
     */
    private $recodexApi;

    /**
     * @var SisScheduleEvents
     */
    private $sisEvents;

    /**
     * @var Users
     */
    private $users;

    /**
     * @param RecodexApiHelper $recodexApi
     * @param SisScheduleEvents $sisEvents
     * @param Users $users
     */
    public function __construct(RecodexApiHelper $recodexApi, SisScheduleEvents $sisEvents, Users $users)
    {
        parent::__construct();
        $this->recodexApi = $recodexApi;
        $this->sisEvents = $sisEvents;
        $this->users = $users;
    }

    /**
     * Register the command.
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)->setDescription('Create a new group in ReCodEx.');
        $this->addArgument('eventId', InputArgument::REQUIRED, 'The SIS ID of the event associated with the group.');
        $this->addArgument('parentId', InputArgument::REQUIRED, 'ReCodEx ID of the the parent group.');
        $this->addArgument('adminId', InputArgument::REQUIRED, 'ReCodEx ID of the admin of the newly created group.');
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

        $eventId = $input->getArgument('eventId');
        $event = $this->sisEvents->findBySisId($eventId);
        if (!$event) {
            $output->writeln("Event with ID $eventId not found.");
            return Command::FAILURE;
        }
        $parentId = $input->getArgument('parentId');
        $adminId = $input->getArgument('adminId');
        $admin = $this->users->get($adminId);
        if (!$admin) {
            $output->writeln("Admin with ID $adminId not found.");
            return Command::FAILURE;
        }

        $token = trim($this->prompt('Auth token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }
        $this->recodexApi->setAuthToken($token);

        $output->writeln("Creating group for event '$eventId' under parent group '$parentId'...");
        $this->recodexApi->createGroup($event, $parentId, $admin);

        return Command::SUCCESS;
    }
}
