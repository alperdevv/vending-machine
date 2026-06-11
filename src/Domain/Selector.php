<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

/**
 * The code a customer presses to choose a product, in canonical form.
 *
 * A selector is case- and whitespace-insensitive: "water", " WATER " and "Water"
 * all name the same product. Canonicalising once, here, keeps that rule in a
 * single place so the way a product is registered and the way it is looked up
 * cannot drift apart. A selector that canonicalises to nothing is rejected.
 */
final readonly class Selector
{
    private function __construct(private string $value)
    {
    }

    public static function fromInput(string $raw): self
    {
        $value = strtoupper(trim($raw));
        if ($value === '') {
            throw new InvalidArgumentException('A product selector cannot be blank.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
