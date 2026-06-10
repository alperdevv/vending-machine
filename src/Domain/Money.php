<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

final readonly class Money
{
    private function __construct(private int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException("A Money amount must be zero or positive; received {$cents} cents.");
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->cents >= $other->cents;
    }
}
