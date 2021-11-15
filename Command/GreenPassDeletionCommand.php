<?php

declare(strict_types=1);

namespace Stadline\Resamania2Bundle\Lib\GreenPass\Command;

use Stadline\Resamania2Bundle\Lib\Command\Command\ReportableCommand;
use Stadline\Resamania2Bundle\Lib\Date\Service\DateManipulator;
use Stadline\Resamania2Bundle\Lib\GreenPass\Manager\GreenPassManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GreenPassDeletionCommand extends ReportableCommand
{
    protected static $defaultName = 'stadline:green-pass:delete';

    private GreenPassManager $manager;

    public function __construct(GreenPassManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Command launched every night to delete expired Green Passes')
            ->addOption('date', 'd', InputOption::VALUE_OPTIONAL, 'Date to execute command', 'now')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = $input->hasOption('date')
            ? new \DateTime($input->getOption('date'))
            : DateManipulator::createNow()
        ;

        try {
            $deletedGreenPasses = $this->manager->removeExpired($date);
            $this->report(sprintf('%d Green Pass removed', $deletedGreenPasses));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->report($exception->getMessage());

            return self::ERROR;
        }
    }
}
