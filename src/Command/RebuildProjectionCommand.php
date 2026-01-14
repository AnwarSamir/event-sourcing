<?php

namespace App\Command;

use App\Application\Projection\OrderProjection;
use App\Infrastructure\EventStore\EventStoreInterface;
use App\Repository\DomainEventRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rebuild-projection',
    description: 'Rebuild the order projection from all events in the event store'
)]
class RebuildProjectionCommand extends Command
{
    public function __construct(
        private readonly DomainEventRepository $eventRepository,
        private readonly EventStoreInterface $eventStore,
        private readonly OrderProjection $projection
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Rebuilding Order Projection');

        // Get all unique aggregate IDs
        $aggregateIds = $this->eventRepository->createQueryBuilder('e')
            ->select('DISTINCT e.aggregateId')
            ->getQuery()
            ->getResult();

        $aggregateIds = array_column($aggregateIds, 'aggregateId');
        
        if (empty($aggregateIds)) {
            $io->warning('No events found in the event store.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d orders to rebuild', count($aggregateIds)));

        $progressBar = $io->createProgressBar(count($aggregateIds));
        $progressBar->start();

        $processed = 0;
        foreach ($aggregateIds as $aggregateId) {
            try {
                // Get all events for this aggregate
                $events = $this->eventStore->getEvents($aggregateId);
                
                // Apply each event to the projection
                foreach ($events as $event) {
                    $this->projection->handle($event);
                }
                
                $processed++;
            } catch (\Exception $e) {
                $io->error(sprintf('Error processing order %s: %s', $aggregateId, $e->getMessage()));
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);
        $io->success(sprintf('Successfully rebuilt projection for %d orders', $processed));

        return Command::SUCCESS;
    }
}
