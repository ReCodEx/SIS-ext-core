<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remove external attribute from ReCodEx group.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
#[AsCommand(name: 'recodex:remove-attribute', description: 'Remove external attribute from ReCodEx group.')]
class RecodexRemoveAttribute extends BaseCommand
{
    /**
     * @var RecodexApiHelper
     */
    private $recodexApi;

    /**
     * @param RecodexApiHelper $recodexApi
     */
    public function __construct(RecodexApiHelper $recodexApi)
    {
        parent::__construct();
        $this->recodexApi = $recodexApi;
    }

    /**
     * Register the command.
     */
    protected function configure()
    {
        $this->addArgument(
            'groupId',
            InputArgument::REQUIRED,
            'ID of the group from which the attribute will be removed.'
        );
        $this->addArgument('key', InputArgument::REQUIRED, 'The key of the attribute being removed.');
        $this->addArgument('value', InputArgument::REQUIRED, 'The value of the attribute being removed.');
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
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $token = trim($this->prompt('Auth token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }
        $this->recodexApi->setAuthToken($token);

        $output->writeln("Removing attribute '$key' with value '$value' from group '$groupId'...");
        $this->recodexApi->removeAttribute($groupId, $key, $value);

        return Command::SUCCESS;
    }
}
