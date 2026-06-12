<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Money;

final class CoinSetTest extends TestCase
{
    #[Test]
    public function an_empty_set_holds_no_coins_and_totals_zero(): void
    {
        $set = CoinSet::empty();

        foreach (Coin::cases() as $coin) {
            self::assertSame(0, $set->countOf($coin));
        }
        self::assertTrue($set->total()->equals(Money::zero()));
    }

    #[Test]
    public function adding_a_coin_raises_the_total_by_its_value(): void
    {
        $set = CoinSet::empty()->add(Coin::TwentyFiveCents);

        self::assertTrue($set->total()->equals(Money::fromCents(25)));
    }

    #[Test]
    public function it_counts_each_denomination_independently(): void
    {
        $set = CoinSet::fromCoins(Coin::TenCents, Coin::TwentyFiveCents, Coin::TenCents);

        self::assertSame(2, $set->countOf(Coin::TenCents));
        self::assertSame(1, $set->countOf(Coin::TwentyFiveCents));
        self::assertSame(0, $set->countOf(Coin::FiveCents));
        self::assertTrue($set->total()->equals(Money::fromCents(45)));
    }

    #[Test]
    public function adding_returns_a_new_set_and_leaves_the_original_untouched(): void
    {
        $original = CoinSet::fromCoins(Coin::FiveCents);

        $grown = $original->add(Coin::FiveCents);

        self::assertSame(1, $original->countOf(Coin::FiveCents));
        self::assertSame(2, $grown->countOf(Coin::FiveCents));
    }

    #[Test]
    public function subtracting_removes_coins_per_denomination(): void
    {
        $set = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TwentyFiveCents, Coin::TenCents);

        $remaining = $set->subtract(CoinSet::fromCoins(Coin::TwentyFiveCents));

        self::assertSame(1, $remaining->countOf(Coin::TwentyFiveCents));
        self::assertSame(1, $remaining->countOf(Coin::TenCents));
        self::assertSame(2, $set->countOf(Coin::TwentyFiveCents));
    }

    #[Test]
    public function subtracting_more_coins_than_held_is_rejected(): void
    {
        $set = CoinSet::fromCoins(Coin::TenCents);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Overdraw of 10c: removal of 2 exceeds the 1 on hand.');

        $set->subtract(CoinSet::fromCoins(Coin::TenCents, Coin::TenCents));
    }

    #[Test]
    public function subtracting_a_denomination_that_is_not_held_is_rejected(): void
    {
        $set = CoinSet::fromCoins(Coin::TenCents);

        $this->expectException(InvalidArgumentException::class);

        $set->subtract(CoinSet::fromCoins(Coin::FiveCents));
    }

    #[Test]
    public function sets_with_the_same_coins_are_equal_regardless_of_order(): void
    {
        $a = CoinSet::fromCoins(Coin::FiveCents, Coin::TwentyFiveCents, Coin::FiveCents);
        $b = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::FiveCents, Coin::FiveCents);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals(CoinSet::fromCoins(Coin::FiveCents)));
    }

    #[Test]
    public function merging_combines_the_tallies_per_denomination(): void
    {
        $a = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents);
        $b = CoinSet::fromCoins(Coin::TenCents, Coin::FiveCents);

        $merged = $a->merge($b);

        self::assertSame(1, $merged->countOf(Coin::TwentyFiveCents));
        self::assertSame(2, $merged->countOf(Coin::TenCents));
        self::assertSame(1, $merged->countOf(Coin::FiveCents));
        self::assertTrue($merged->total()->equals(Money::fromCents(50)));
    }

    #[Test]
    public function merging_returns_a_new_set_and_leaves_both_operands_untouched(): void
    {
        $a = CoinSet::fromCoins(Coin::TenCents);
        $b = CoinSet::fromCoins(Coin::TenCents);

        $a->merge($b);

        self::assertSame(1, $a->countOf(Coin::TenCents));
        self::assertSame(1, $b->countOf(Coin::TenCents));
    }

    #[Test]
    public function merging_with_the_empty_set_changes_nothing(): void
    {
        $set = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::FiveCents);

        self::assertTrue($set->merge(CoinSet::empty())->equals($set));
        self::assertTrue(CoinSet::empty()->merge($set)->equals($set));
    }

    #[Test]
    public function subtracting_a_denomination_down_to_zero_removes_it_entirely(): void
    {
        $set = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TwentyFiveCents);

        $drained = $set->subtract(CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TwentyFiveCents));

        self::assertSame(0, $drained->countOf(Coin::TwentyFiveCents));
        self::assertTrue($drained->equals(CoinSet::empty()));
        self::assertTrue($drained->total()->equals(Money::zero()));
    }
}
