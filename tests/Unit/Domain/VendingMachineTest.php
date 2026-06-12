<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Catalog;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Exception\CannotMakeChange;
use Vending\Domain\Exception\InsufficientFunds;
use Vending\Domain\Exception\ProductOutOfStock;
use Vending\Domain\Exception\UnknownProduct;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Product;
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

    #[Test]
    public function buying_with_exact_payment_dispenses_the_product_and_no_change(): void
    {
        $machine = $this->machine(inventory: Inventory::empty()->withStock($this->selector('SODA'), 1));
        $machine->insert(Coin::OneHundredCents);
        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TwentyFiveCents);

        $vend = $machine->buy($this->selector('SODA'));

        self::assertTrue($vend->product()->selector()->equals($this->selector('SODA')));
        self::assertTrue($vend->change()->equals(CoinSet::empty()));
        self::assertTrue($machine->balance()->equals(Money::zero()));
        self::assertTrue($machine->drawer()->total()->equals(Money::fromCents(150)));
    }

    #[Test]
    public function buying_with_excess_payment_returns_change_from_the_drawer(): void
    {
        $machine = $this->machine(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents),
        );
        $machine->insert(Coin::OneHundredCents);

        $vend = $machine->buy($this->selector('WATER'));

        self::assertTrue($vend->change()->equals(CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents)));
        self::assertTrue($machine->balance()->equals(Money::zero()));
        self::assertTrue($machine->drawer()->equals(CoinSet::fromCoins(Coin::OneHundredCents)));
    }

    #[Test]
    public function change_can_be_paid_out_of_the_coins_just_inserted(): void
    {
        $machine = $this->machine(inventory: Inventory::empty()->withStock($this->selector('JUICE'), 1));
        $machine->insert(Coin::OneHundredCents);
        $machine->insert(Coin::TenCents);
        $machine->insert(Coin::FiveCents);

        $vend = $machine->buy($this->selector('JUICE'));

        self::assertTrue($vend->change()->equals(CoinSet::fromCoins(Coin::TenCents, Coin::FiveCents)));
        self::assertTrue($machine->drawer()->equals(CoinSet::fromCoins(Coin::OneHundredCents)));
    }

    #[Test]
    public function a_sale_grows_the_drawer_by_exactly_the_price(): void
    {
        $machine = $this->machine(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::TenCents, Coin::TenCents, Coin::TenCents, Coin::FiveCents),
        );
        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TwentyFiveCents);

        $machine->buy($this->selector('WATER'));

        self::assertTrue($machine->drawer()->total()->equals(Money::fromCents(35 + 65)));
    }

    #[Test]
    public function the_hundred_cent_coin_is_never_dispensed_as_change(): void
    {
        $machine = $this->machine(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::OneHundredCents, Coin::TwentyFiveCents, Coin::TenCents),
        );
        $machine->insert(Coin::OneHundredCents);

        $vend = $machine->buy($this->selector('WATER'));

        self::assertSame(0, $vend->change()->countOf(Coin::OneHundredCents));
        self::assertTrue($vend->change()->equals(CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents)));
    }

    #[Test]
    public function buying_an_unknown_selector_is_refused(): void
    {
        $machine = $this->machine();

        $this->expectException(UnknownProduct::class);

        $machine->buy($this->selector('COFFEE'));
    }

    #[Test]
    public function buying_without_enough_money_is_refused(): void
    {
        $machine = $this->machine(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));
        $machine->insert(Coin::TwentyFiveCents);

        $this->expectException(InsufficientFunds::class);

        $machine->buy($this->selector('WATER'));
    }

    #[Test]
    public function buying_a_sold_out_product_is_refused(): void
    {
        $machine = $this->machine();
        $machine->insert(Coin::OneHundredCents);

        $this->expectException(ProductOutOfStock::class);

        $machine->buy($this->selector('WATER'));
    }

    #[Test]
    public function a_sale_whose_change_cannot_be_paid_is_refused(): void
    {
        $machine = $this->machine(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::TwentyFiveCents),
        );
        $machine->insert(Coin::OneHundredCents);

        $this->expectException(CannotMakeChange::class);

        $machine->buy($this->selector('WATER'));
    }

    #[Test]
    public function a_refused_sale_leaves_the_machine_untouched(): void
    {
        $drawer = CoinSet::fromCoins(Coin::TwentyFiveCents);
        $machine = $this->machine(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: $drawer,
        );
        $machine->insert(Coin::OneHundredCents);

        try {
            $machine->buy($this->selector('WATER'));
            self::fail('The sale should have been refused.');
        } catch (CannotMakeChange) {
        }

        self::assertTrue($machine->balance()->equals(Money::fromCents(100)));
        self::assertTrue($machine->drawer()->equals($drawer));
        self::assertSame(1, $machine->stockOf($this->selector('WATER')));
        self::assertTrue($machine->returnCoins()->equals(CoinSet::fromCoins(Coin::OneHundredCents)));
    }

    #[Test]
    public function the_machine_still_sells_after_refusing_a_sale(): void
    {
        $machine = $this->machine(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));
        $machine->insert(Coin::OneHundredCents);

        try {
            $machine->buy($this->selector('WATER'));
            self::fail('The sale should have been refused.');
        } catch (CannotMakeChange) {
        }

        $machine->returnCoins();
        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TwentyFiveCents);
        $machine->insert(Coin::TenCents);
        $machine->insert(Coin::FiveCents);

        $vend = $machine->buy($this->selector('WATER'));

        self::assertTrue($vend->change()->equals(CoinSet::empty()));
        self::assertSame(0, $machine->stockOf($this->selector('WATER')));
    }

    #[Test]
    public function selling_the_last_unit_leaves_the_product_sold_out(): void
    {
        $machine = $this->machine(inventory: Inventory::empty()->withStock($this->selector('JUICE'), 1));
        $machine->insert(Coin::OneHundredCents);
        $machine->buy($this->selector('JUICE'));

        $machine->insert(Coin::OneHundredCents);

        $this->expectException(ProductOutOfStock::class);

        $machine->buy($this->selector('JUICE'));
    }

    private function machine(
        ?Catalog $catalog = null,
        ?Inventory $inventory = null,
        ?CoinSet $drawer = null,
    ): VendingMachine {
        return VendingMachine::assemble(
            $catalog ?? Catalog::from(
                Product::of('WATER', Money::fromCents(65)),
                Product::of('JUICE', Money::fromCents(100)),
                Product::of('SODA', Money::fromCents(150)),
            ),
            $inventory ?? Inventory::empty(),
            $drawer ?? CoinSet::empty(),
            new ChangeMaker(),
        );
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
