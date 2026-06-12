<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Application\CustomerActions;
use Vending\Domain\Catalog;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Exception\CannotMakeChange;
use Vending\Domain\Exception\InsufficientFunds;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Product;
use Vending\Domain\Selector;
use Vending\Domain\VendingMachine;
use Vending\Infrastructure\InMemoryMachineRepository;

final class CustomerActionsTest extends TestCase
{
    #[Test]
    public function separate_calls_share_the_machine_through_the_repository(): void
    {
        $customer = $this->customer(inventory: Inventory::empty()->withStock($this->selector('SODA'), 1));

        $customer->insertCoin(Coin::OneHundredCents);
        $customer->insertCoin(Coin::TwentyFiveCents);
        $customer->insertCoin(Coin::TwentyFiveCents);
        $vend = $customer->buyProduct($this->selector('SODA'));

        self::assertTrue($vend->product()->selector()->equals($this->selector('SODA')));
        self::assertTrue($vend->change()->equals(CoinSet::empty()));
    }

    #[Test]
    public function buying_with_excess_payment_returns_change(): void
    {
        $customer = $this->customer(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents),
        );

        $customer->insertCoin(Coin::OneHundredCents);
        $vend = $customer->buyProduct($this->selector('WATER'));

        self::assertTrue($vend->change()->equals(CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents)));
    }

    #[Test]
    public function the_balance_reports_the_money_inserted_so_far(): void
    {
        $customer = $this->customer();

        $customer->insertCoin(Coin::TwentyFiveCents);
        $customer->insertCoin(Coin::TenCents);

        self::assertTrue($customer->balance()->equals(Money::fromCents(35)));
    }

    #[Test]
    public function returning_coins_gives_back_the_exact_pieces(): void
    {
        $customer = $this->customer();

        $customer->insertCoin(Coin::TenCents);
        $customer->insertCoin(Coin::TenCents);

        self::assertTrue($customer->returnCoins()->equals(CoinSet::fromCoins(Coin::TenCents, Coin::TenCents)));
    }

    #[Test]
    public function a_flow_refusal_crosses_the_application_untouched(): void
    {
        $customer = $this->customer(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));
        $customer->insertCoin(Coin::TwentyFiveCents);

        $this->expectException(InsufficientFunds::class);

        $customer->buyProduct($this->selector('WATER'));
    }

    #[Test]
    public function a_refused_purchase_leaves_the_session_returnable(): void
    {
        $customer = $this->customer(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));
        $customer->insertCoin(Coin::OneHundredCents);

        try {
            $customer->buyProduct($this->selector('WATER'));
            self::fail('The purchase should have been refused.');
        } catch (CannotMakeChange) {
        }

        self::assertTrue($customer->returnCoins()->equals(CoinSet::fromCoins(Coin::OneHundredCents)));
    }

    private function customer(?Inventory $inventory = null, ?CoinSet $drawer = null): CustomerActions
    {
        $machine = VendingMachine::assemble(
            Catalog::from(
                Product::of('WATER', Money::fromCents(65)),
                Product::of('JUICE', Money::fromCents(100)),
                Product::of('SODA', Money::fromCents(150)),
            ),
            $inventory ?? Inventory::empty(),
            $drawer ?? CoinSet::empty(),
            new ChangeMaker(),
        );

        return new CustomerActions(new InMemoryMachineRepository($machine));
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
