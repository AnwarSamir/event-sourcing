<?php

namespace App\Domain\Trigger;

final class Trigger
{
    public function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly array $payload,
        private readonly \DateTimeImmutable $receivedAt = new \DateTimeImmutable()
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getPayloadValue(string $key, $default = null)
    {
        return $this->payload[$key] ?? $default;
    }
}
