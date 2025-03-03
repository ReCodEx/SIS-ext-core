<?php

namespace App\Console;

use App\Helpers\SisHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test SIS API by fetching all courses related to a user.
 */
class SisGetCourse extends BaseCommand
{
    protected static $defaultName = 'sis:course';

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
        $this->setName(self::$defaultName)->setDescription('Get courses from SIS related to given user.');
        $this->addArgument('ukco', InputArgument::REQUIRED, 'SIS ID of the user whose courses are being loaded.');
        $this->addOption(
            'year',
            null,
            InputOption::VALUE_REQUIRED,
            'Calendar year in which the required academic year starts.',
            (int)date('Y') - ((int)date('m') < 10 ? 1 : 0)
        );
        $this->addOption(
            'term',
            null,
            InputOption::VALUE_REQUIRED,
            'Selected semester (1=winter term, 2=summer term)',
            1
        );
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
        $year = (int)$input->getOption('year');
        $term = (int)$input->getOption('term');
        foreach ($this->sis->getCourses($ukco, $year, $term) as $course) {
            $output->writeln($course->getCode() . ': ' . $course->getCaption('en'));
        }

        return Command::SUCCESS;
    }
}
