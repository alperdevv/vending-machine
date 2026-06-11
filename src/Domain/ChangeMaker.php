<?php

declare(strict_types=1);

namespace Vending\Domain;

use function count;

/**
 * Works out which coins to hand back as change, drawing on a finite drawer.
 *
 * Change must be paid in real coins from a limited supply, which makes this the
 * bounded coin-change problem, not the textbook one with unlimited coins. A
 * greedy pick is unsound there: with {25x1, 10x3} it grabs the 25 and strands
 * 5c it cannot cover, declaring 30c impossible when 10+10+10 was waiting. So the
 * search tries each denomination from large to small but backtracks — when a
 * choice leads nowhere it drops a coin of that denomination and tries again,
 * which makes it complete: if any combination exists, it is found.
 *
 * The result is a CoinSet of the coins to dispense, CoinSet::empty() when exact
 * payment leaves nothing to return, or null when no combination fits and the
 * sale must be refused. The one change-specific rule lives here, not in Coin or
 * CoinSet: the 100c coin is taken as payment but never dispensed as change, so
 * it is excluded from the denominations the search may use.
 */
final class ChangeMaker
{
    public function changeFor(Money $due, CoinSet $available): ?CoinSet
    {
        return $this->resolve($due->cents(), $this->dispensableDenominations(), 0, $available);
    }

    /**
     * @param list<Coin> $denominations highest value first
     */
    private function resolve(int $due, array $denominations, int $index, CoinSet $available): ?CoinSet
    {
        if ($due === 0) {
            return CoinSet::empty();
        }

        if ($index >= count($denominations)) {
            return null;
        }

        $coin = $denominations[$index];
        $mostUsable = min($available->countOf($coin), intdiv($due, $coin->value));

        for ($used = $mostUsable; $used >= 0; $used--) {
            $rest = $this->resolve($due - $used * $coin->value, $denominations, $index + 1, $available);

            if ($rest !== null) {
                return $this->withCoins($rest, $coin, $used);
            }
        }

        return null;
    }

    private function withCoins(CoinSet $set, Coin $coin, int $count): CoinSet
    {
        for ($i = 0; $i < $count; $i++) {
            $set = $set->add($coin);
        }

        return $set;
    }

    /**
     * @return list<Coin>
     */
    private function dispensableDenominations(): array
    {
        $denominations = array_filter(
            Coin::cases(),
            static fn (Coin $coin): bool => $coin !== Coin::OneHundredCents,
        );

        usort($denominations, static fn (Coin $a, Coin $b): int => $b->value <=> $a->value);

        return $denominations;
    }
}
