<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

/**
 * How many units of each product a machine holds, by selector.
 *
 * Immutable, and keyed by selector alone: it knows nothing of prices or which
 * selectors are real products — tying stock to the catalog is the aggregate's
 * job. A selector with no units is dropped, so the tracked keys are exactly the
 * products currently in stock.
 *
 * withStock() sets a selector's count outright, which is what a service refill
 * declares: an absolute amount, not a delta. dispense() removes a single unit
 * and fails closed on empty — a precondition, not a customer outcome: a sale
 * checks availability first, so reaching here empty is a caller error. "Sold
 * out" as something a customer is told is the aggregate's flow to raise.
 */
final readonly class Inventory
{
    /**
     * @param array<string, positive-int> $unitsBySelector
     */
    private function __construct(private array $unitsBySelector)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public function withStock(Selector $selector, int $count): self
    {
        if ($count < 0) {
            throw new InvalidArgumentException("Stock cannot be negative; got {$count} for '{$selector->value()}'.");
        }

        $units = $this->unitsBySelector;
        if ($count === 0) {
            unset($units[$selector->value()]);
        } else {
            $units[$selector->value()] = $count;
        }

        return new self($units);
    }

    public function dispense(Selector $selector): self
    {
        $remaining = $this->stockOf($selector) - 1;
        if ($remaining < 0) {
            throw new InvalidArgumentException("No stock to dispense for '{$selector->value()}'.");
        }

        $units = $this->unitsBySelector;
        if ($remaining === 0) {
            unset($units[$selector->value()]);
        } else {
            $units[$selector->value()] = $remaining;
        }

        return new self($units);
    }

    public function stockOf(Selector $selector): int
    {
        return $this->unitsBySelector[$selector->value()] ?? 0;
    }

    public function hasStock(Selector $selector): bool
    {
        return $this->stockOf($selector) > 0;
    }
}
