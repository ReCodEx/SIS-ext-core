<?php

namespace App\Console;

use App\Helpers\RecodexApiHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test the ReCodEx API by manually translating temp token into full token.
 * This command is mainly for debugging purposes.
 */
class RecodexToken extends BaseCommand
{
    protected static $defaultName = 'recodex:token';

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
        $this->setName(self::$defaultName)->setDescription('Translate tmp token into full token and get user info.');
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

        $token = trim($this->prompt('Temp. token: '));
        if (!$token) {
            $output->writeln('No token given, terminating...');
            return Command::SUCCESS;
        }

        $this->recodexApi->setAuthToken($token);
        $data = $this->recodexApi->getTokenAndUser();
        $output->writeln('<info>Token:</info> ' . $data['accessToken']);
        $output->writeln('<info>User:</info> ' . json_encode($data['user'], JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
