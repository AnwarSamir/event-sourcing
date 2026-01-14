<?php

namespace App\Domain\Command;

interface Command
{
    public function getAggregateId(): string;
}
