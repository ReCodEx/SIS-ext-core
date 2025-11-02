<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use App\Helpers\RecodexGroup;
use App\Model\Entity\SisAffiliation;
use App\Model\Repository\SisScheduleEvents;
use App\Model\Repository\Users;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load groups from ReCodEx API for given user and print them out.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
#[AsCommand(name: 'recodex:groups', description: 'Load groups from ReCodEx API for given user.')]
class RecodexGroups extends BaseCommand
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
     * @var SisScheduleEvents
     */
    private $sisEvents;

    /**
     * @param RecodexApiHelper $recodexApi
     * @param Users $users
     * @param SisScheduleEvents $sisEvents
     */
    public function __construct(RecodexApiHelper $recodexApi, Users $users, SisScheduleEvents $sisEvents)
    {
        parent::__construct();
        $this->recodexApi = $recodexApi;
        $this->users = $users;
        $this->sisEvents = $sisEvents;
    }

    /**
     * Register the command.
     */
    protected function configure()
    {
        $this->addArgument('ukco', InputArgument::REQUIRED, 'SIS ID of the user whose groups are being loaded.');
    }

    private static function printGroup(OutputInterface $output, RecodexGroup $group, int $level = 0): void
    {
        $admins = array_map(function ($admin) {
            return $admin->lastName;
        }, $group->admins);
        $admins = ($admins) ? ('  <fg=gray>\<' . implode(', ', $admins) . '\></>') : '';

        $membership = ($group->membership) ? ('  <fg=green>(' . substr($group->membership, 0, 3) . ')</>') : '';

        $attributes = [];
        foreach ($group->attributes as $key => $values) {
            foreach ($values as $value) {
                $attributes[] = "$key=$value";
            }
        }
        $attributes = $attributes ? ('  <fg=yellow>[' . implode(', ', $attributes) . ']</>') : '';

        $output->writeln(str_repeat('    ', $level) . ($group->name['en'] ?? $group->name['cs'])
            . $admins . $membership . $attributes);

        foreach ($group->children as $child) {
            self::printGroup($output, $child, $level + 1);
        }
    }

    private static function printGroups(OutputInterface $output, array $groups): void
    {
        $rootGroups = RecodexGroup::populateChildren($groups);
        foreach ($rootGroups as $group) {
            self::printGroup($output, $group);
        }
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

        $ukco = $input->getArgument('ukco');
        $user = $this->users->findOneBy(['sisId' => $ukco]);
        if (!$user) {
            $output->writeln('<error>User not found.</error>');
            return Command::FAILURE;
        }

        $token = trim($this->prompt('Auth token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }

        $this->recodexApi->setAuthToken($token);
        $groups = $this->recodexApi->getGroups($user);

        // Print student's view
        if ($user->getRole() === 'student' || $user->getRole() === 'supervisor-student') {
            $events = $this->sisEvents->allEventsOfUser($user, null, SisAffiliation::TYPE_STUDENT);
            $eventIds = array_map(fn($e) => $e->getSisId(), $events);
            $studGroups = RecodexGroup::pruneForStudent($groups, $eventIds);
            $output->writeln('<info>Student view:</info>');
            $output->writeln('-------------');
            self::printGroups($output, $studGroups);
        }

        if ($user->getRole() === 'supervisor-student') {
            $output->writeln("\n\n"); // this role prints both views (students and teachers)
        }

        // Print teacher's view
        if ($user->getRole() !== 'student') {
            $events = $this->sisEvents->allEventsOfUser(
                $user,
                null,
                [SisAffiliation::TYPE_TEACHER, SisAffiliation::TYPE_GUARANTOR]
            );
            $courseIds = array_map(fn($e) => $e->getCourse()->getCode(), $events);
            $teachGroups = RecodexGroup::pruneForTeacher($groups, $courseIds, []);
            $output->writeln('<info>Teacher view:</info>');
            $output->writeln('-------------');
            self::printGroups($output, $teachGroups);
        }

        return Command::SUCCESS;
    }
}
