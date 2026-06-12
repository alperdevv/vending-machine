<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Selector;
use Vending\Domain\VendingMachine;

final class VendingMachineTest extends TestCase
{
    #[Test]
    public function it_starts_with_no_money_inserted(): void
    {
        $machine = $this->machine();

        self::assertTrue($machine->balance()->equals(Money::zero()));
    }

    #[Test]
    public function inserting_coins_accumulates_the_balance(): void
    {
        $machine = $this->machine();

        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TenCents);

        self::assertTrue($machine->balance()->equals(Money::fromCents(35)));
    }

    #[Test]
    public function returning_coins_gives_back_exactly_the_inserted_pieces(): void
    {
        $machine = $this->machine();
        $machine->insert(Coin::TenCents);
        $machine->insert(Coin::TenCents);

        $returned = $machine->returnCoins();

        self::assertTrue($returned->equals(CoinSet::fromCoins(Coin::TenCents, Coin::TenCents)));
    }

    #[Test]
    public function returning_coins_empties_the_session(): void
    {
        $machine = $this->machine();
        $machine->insert(Coin::OneHundredCents);

        $machine->returnCoins();

        self::assertTrue($machine->balance()->equals(Money::zero()));
        self::assertTrue($machine->returnCoins()->equals(CoinSet::empty()));
    }

    #[Test]
    public function returning_coins_with_nothing_inserted_returns_the_empty_set(): void
    {
        $machine = $this->machine();

        self::assertTrue($machine->returnCoins()->equals(CoinSet::empty()));
    }

    #[Test]
    public function inserted_coins_do_not_enter_the_drawer(): void
    {
        $drawer = CoinSet::fromCoins(Coin::TwentyFiveCents);
        $machine = $this->machine(drawer: $drawer);

        $machine->insert(Coin::OneHundredCents);

        self::assertTrue($machine->drawer()->equals($drawer));
    }

    #[Test]
    public function it_exposes_the_drawer_it_was_assembled_with(): void
    {
        $drawer = CoinSet::fromCoins(Coin::FiveCents, Coin::TenCents);

        $machine = $this->machine(drawer: $drawer);

        self::assertTrue($machine->drawer()->equals($drawer));
    }

    #[Test]
    public function it_reports_stock_by_selector(): void
    {
        $inventory = Inventory::empty()->withStock($this->selector('WATER'), 2);

        $machine = $this->machine(inventory: $inventory);

        self::assertSame(2, $machine->stockOf($this->selector('WATER')));
        self::assertSame(0, $machine->stockOf($this->selector('SODA')));
    }

    private function machine(?Inventory $inventory = null, ?CoinSet $drawer = null): VendingMachine
    {
        return VendingMachine::assemble(
            $inventory ?? Inventory::empty(),
            $drawer ?? CoinSet::empty(),
        );
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
