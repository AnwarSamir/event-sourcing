<?php

namespace App\Application\CommandHandler;

use App\Domain\Command\Command;

interface CommandHandlerInterface
{
    public function handle(Command $command): void;
}
