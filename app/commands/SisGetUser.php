<?php

namespace App\Console;

use App\Helpers\SisHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tracy\Debugger;
use Exception;

/**
 * Test SIS API by fetching personal data of a user.
 */
class SisGetUser extends BaseCommand
{
    protected static $defaultName = 'sis:user';

    /**
     * @var SisHelper
     */
    private $sis;

    /**
     * @param SisHelper $sis
     */
    public function __construct(SisHelper $sis)
    {
        parent::__construct();
        $this->sis = $sis;
    }

    /**
     * Register the command.
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)->setDescription('Get personal data of a user from SIS.');
        $this->addArgument('ukco', InputArgument::REQUIRED, 'SIS ID of the user.');
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

        $ukco = $input->getArgument('ukco');
        try {
            $record = $this->sis->getUserRecord($ukco);
        } catch (Exception $e) {
            Debugger::log($e);
            throw $e;
        }
        $output->writeln(json_encode($record, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
