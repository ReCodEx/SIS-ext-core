<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add external attribute to ReCodEx group.
 * This command is mainly designed for debugging ReCodEx API integration.
 */
#[AsCommand(name: 'recodex:add-attribute', description: 'Add external attribute to ReCodEx group.')]
class RecodexAddAttribute extends BaseCommand
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
        $this->addArgument('groupId', InputArgument::REQUIRED, 'ID of the group to which the attribute will be added.');
        $this->addArgument('key', InputArgument::REQUIRED, 'The key of the attribute being added.');
        $this->addArgument('value', InputArgument::REQUIRED, 'The value of the attribute being added.');
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

        $output->writeln("Adding attribute '$key' with value '$value' to group '$groupId'...");
        $this->recodexApi->addAttribute($groupId, $key, $value);

        return Command::SUCCESS;
    }
}
