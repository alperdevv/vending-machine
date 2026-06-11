<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

/**
 * A purchasable item: the selector a customer presses and the price it costs.
 *
 * Items are data the machine is expected to grow, not a fixed set, so a product
 * is a value object identified by its selector rather than a hardcoded type.
 *
 * The price must be a positive multiple of the coin granularity — the gcd of the
 * accepted denominations. Every accepted coin is a multiple of that step, so any
 * inserted total is; requiring the price to be one too keeps the change due a
 * multiple of it, and therefore always payable from the coins. Deriving the step
 * from Coin instead of hardcoding it means adding or changing a denomination
 * adjusts the rule rather than silently breaking it. Checked fail-fast at
 * construction, since a malformed price is a configuration error, not a flow.
 */
final readonly class Product
{
    private function __construct(
        private string $selector,
        private Money $price,
    ) {
    }

    public static function of(string $selector, Money $price): self
    {
        if (trim($selector) === '') {
            throw new InvalidArgumentException('A product selector cannot be blank.');
        }

        if ($price->cents() <= 0) {
            throw new InvalidArgumentException("A product price must be positive; got {$price->cents()} cents.");
        }

        $step = self::coinGranularity();
        if ($price->cents() % $step !== 0) {
            throw new InvalidArgumentException(
                "A product price must be a multiple of {$step} cents so change stays payable; got {$price->cents()}.",
            );
        }

        return new self($selector, $price);
    }

    public function selector(): string
    {
        return $this->selector;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function equals(self $other): bool
    {
        return $this->selector === $other->selector && $this->price->equals($other->price);
    }

    private static function coinGranularity(): int
    {
        $step = 0;
        foreach (Coin::cases() as $coin) {
            $step = self::gcd($step, $coin->value);
        }

        return $step;
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a;
    }
}
