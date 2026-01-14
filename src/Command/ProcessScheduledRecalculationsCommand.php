<?php

namespace App\Command;

use App\Application\TriggerHandler\TriggerHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-scheduled-recalculations',
    description: 'Process triggers that are scheduled for recalculation'
)]
class ProcessScheduledRecalculationsCommand extends Command
{
    public function __construct(
        private readonly TriggerHandler $triggerHandler
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();

        $io->title('Processing Scheduled Recalculations');
        $io->text('Current time: ' . $now->format('Y-m-d H:i:s'));

        $processed = $this->triggerHandler->processScheduledRecalculation($now);

        if ($processed > 0) {
            $io->success("Processed {$processed} scheduled recalculations");
        } else {
            $io->info('No triggers scheduled for recalculation at this time');
        }

        return Command::SUCCESS;
    }
}
