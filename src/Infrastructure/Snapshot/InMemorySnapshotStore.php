<?php

namespace App\Infrastructure\Snapshot;

use App\Domain\Aggregate\OrderSnapshot;

class InMemorySnapshotStore implements SnapshotStoreInterface
{
    private array $snapshots = [];

    public function save(string $aggregateId, OrderSnapshot $snapshot): void
    {
        $this->snapshots[$aggregateId] = $snapshot;
    }

    public function get(string $aggregateId): ?OrderSnapshot
    {
        return $this->snapshots[$aggregateId] ?? null;
    }
}
