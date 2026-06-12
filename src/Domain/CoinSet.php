<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

/**
 * Coins grouped by denomination with a tally for each — the machine's money
 * seen as countable pieces, not a single figure.
 *
 * Two places need this view: the coins a customer has dropped in so far, and
 * the float the machine pays change out of. Each has to return real pieces
 * drawn from a limited supply, so a bare amount like 1.50 is not enough —
 * what matters is that it is, concretely, six 25c pieces. Every instance is
 * immutable; total() flattens the tally to a Money once a scalar amount is
 * all the caller wants.
 *
 * A denomination's face value in cents keys a strictly positive tally, and a
 * denomination that drops to nothing leaves the map, so the keys are always
 * exactly the pieces on hand. The constructor relies on this rather than
 * checking it: nothing outside hands in the array and every operation here
 * keeps the shape, so a check could never trip and would be dead, untestable
 * code.
 */
final readonly class CoinSet
{
    /**
     * @param array<int, positive-int> $counts
     */
    private function __construct(private array $counts)
    {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromCoins(Coin ...$coins): self
    {
        $counts = [];
        foreach ($coins as $coin) {
            $counts[$coin->value] = ($counts[$coin->value] ?? 0) + 1;
        }

        return new self($counts);
    }

    public function add(Coin $coin): self
    {
        $counts = $this->counts;
        $counts[$coin->value] = ($counts[$coin->value] ?? 0) + 1;

        return new self($counts);
    }

    /**
     * Returns this set plus every coin of $other — the session emptying into
     * the drawer when a sale completes.
     */
    public function merge(self $other): self
    {
        $counts = $this->counts;
        foreach ($other->counts as $cents => $count) {
            $counts[$cents] = ($counts[$cents] ?? 0) + $count;
        }

        return new self($counts);
    }

    /**
     * Returns this set with the coins of $other taken out, one denomination at
     * a time.
     *
     * Precondition: $other is contained in this set. Feasibility of paying
     * change is settled against the available stock before any coin moves, so
     * arriving here short of the coins to remove is a caller bug, not a domain
     * outcome — and is refused outright.
     */
    public function subtract(self $other): self
    {
        $remaining = $this->counts;
        foreach ($other->counts as $cents => $count) {
            $left = ($remaining[$cents] ?? 0) - $count;
            if ($left < 0) {
                throw new InvalidArgumentException(
                    "Overdraw of {$cents}c: removal of {$count} exceeds the " . $this->countOfCents($cents) . ' on hand.',
                );
            }

            if ($left === 0) {
                unset($remaining[$cents]);
            } else {
                $remaining[$cents] = $left;
            }
        }

        return new self($remaining);
    }

    public function countOf(Coin $coin): int
    {
        return $this->countOfCents($coin->value);
    }

    public function total(): Money
    {
        $cents = 0;
        foreach ($this->counts as $value => $count) {
            $cents += $value * $count;
        }

        return Money::fromCents($cents);
    }

    public function equals(self $other): bool
    {
        $mine = $this->counts;
        $theirs = $other->counts;
        ksort($mine);
        ksort($theirs);

        return $mine === $theirs;
    }

    private function countOfCents(int $cents): int
    {
        return $this->counts[$cents] ?? 0;
    }
}
