<?php

namespace App\Infrastructure\Snapshot;

use App\Domain\Aggregate\OrderSnapshot;

interface SnapshotStoreInterface
{
    public function save(string $aggregateId, OrderSnapshot $snapshot): void;
    public function get(string $aggregateId): ?OrderSnapshot;
}
