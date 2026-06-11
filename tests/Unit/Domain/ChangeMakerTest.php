<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Money;

final class ChangeMakerTest extends TestCase
{
    #[Test]
    public function paying_exactly_returns_no_change_rather_than_impossible(): void
    {
        $change = new ChangeMaker()->changeFor(Money::zero(), CoinSet::empty());

        self::assertNotNull($change);
        self::assertTrue($change->equals(CoinSet::empty()));
    }

    #[Test]
    public function it_builds_change_when_the_drawer_is_well_stocked(): void
    {
        $drawer = CoinSet::fromCoins(
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TenCents,
            Coin::TenCents,
            Coin::FiveCents,
            Coin::FiveCents,
        );

        $change = new ChangeMaker()->changeFor(Money::fromCents(30), $drawer);

        self::assertNotNull($change);
        self::assertTrue($change->total()->equals(Money::fromCents(30)));
        $this->assertWithinStock($change, $drawer);
    }

    #[Test]
    public function it_backtracks_past_a_large_coin_to_a_solution_greedy_would_miss(): void
    {
        // 30c from {25x1, 10x3}: a greedy grab of the 25 strands 5c it cannot cover.
        $drawer = CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents, Coin::TenCents, Coin::TenCents);

        $change = new ChangeMaker()->changeFor(Money::fromCents(30), $drawer);

        self::assertNotNull($change);
        self::assertTrue($change->equals(CoinSet::fromCoins(Coin::TenCents, Coin::TenCents, Coin::TenCents)));
        self::assertSame(0, $change->countOf(Coin::TwentyFiveCents));
    }

    #[Test]
    public function it_reports_impossible_when_no_combination_fits(): void
    {
        $drawer = CoinSet::fromCoins(Coin::TenCents);

        $change = new ChangeMaker()->changeFor(Money::fromCents(5), $drawer);

        self::assertNull($change);
    }

    #[Test]
    public function it_never_hands_back_the_hundred_cent_coin_as_change(): void
    {
        $drawer = CoinSet::fromCoins(
            Coin::OneHundredCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
        );

        $change = new ChangeMaker()->changeFor(Money::fromCents(100), $drawer);

        self::assertNotNull($change);
        self::assertSame(0, $change->countOf(Coin::OneHundredCents));
        self::assertTrue($change->equals(CoinSet::fromCoins(
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
        )));
    }

    #[Test]
    public function owing_one_hundred_with_only_a_hundred_cent_coin_is_impossible(): void
    {
        // The exact coin sits in the drawer, yet it can never be dispensed as change.
        $drawer = CoinSet::fromCoins(Coin::OneHundredCents);

        $change = new ChangeMaker()->changeFor(Money::fromCents(100), $drawer);

        self::assertNull($change);
    }

    #[Test]
    public function it_respects_how_many_of_each_denomination_are_available(): void
    {
        // Only one 10c on hand, so 20c cannot be two dimes; it must be 10 + 5 + 5.
        $drawer = CoinSet::fromCoins(Coin::TenCents, Coin::FiveCents, Coin::FiveCents);

        $change = new ChangeMaker()->changeFor(Money::fromCents(20), $drawer);

        self::assertNotNull($change);
        self::assertTrue($change->equals(CoinSet::fromCoins(Coin::TenCents, Coin::FiveCents, Coin::FiveCents)));
        $this->assertWithinStock($change, $drawer);
    }

    #[Test]
    public function the_change_it_returns_adds_up_to_the_amount_due(): void
    {
        $drawer = CoinSet::fromCoins(
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TenCents,
            Coin::TenCents,
            Coin::FiveCents,
            Coin::FiveCents,
        );

        $change = new ChangeMaker()->changeFor(Money::fromCents(40), $drawer);

        self::assertNotNull($change);
        self::assertTrue($change->total()->equals(Money::fromCents(40)));
        $this->assertWithinStock($change, $drawer);
    }

    #[Test]
    public function any_change_it_returns_is_exact_within_stock_and_free_of_hundreds(): void
    {
        // A property sweep: a generous drawer (with a 100c that must never be used) should
        // make every multiple of 5 up to its worth, always exactly and within stock.
        $drawer = CoinSet::fromCoins(
            Coin::OneHundredCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TwentyFiveCents,
            Coin::TenCents,
            Coin::TenCents,
            Coin::TenCents,
            Coin::TenCents,
            Coin::FiveCents,
            Coin::FiveCents,
            Coin::FiveCents,
            Coin::FiveCents,
        );
        $maker = new ChangeMaker();

        for ($due = 0; $due <= 60; $due += 5) {
            $change = $maker->changeFor(Money::fromCents($due), $drawer);

            self::assertNotNull($change, "change for {$due}c should be possible");
            self::assertTrue($change->total()->equals(Money::fromCents($due)), "change for {$due}c must be exact");
            self::assertSame(0, $change->countOf(Coin::OneHundredCents), "change for {$due}c must avoid the 100c coin");
            $this->assertWithinStock($change, $drawer);
        }
    }

    private function assertWithinStock(?CoinSet $change, CoinSet $available): void
    {
        self::assertNotNull($change);

        foreach (Coin::cases() as $coin) {
            self::assertLessThanOrEqual(
                $available->countOf($coin),
                $change->countOf($coin),
                "change uses more {$coin->value}c coins than the drawer holds",
            );
        }
    }
}
