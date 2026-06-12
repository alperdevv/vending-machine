<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Application\MachineRepository;
use Vending\Domain\Catalog;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Product;
use Vending\Domain\Selector;
use Vending\Domain\VendingMachine;

/**
 * The behaviour every MachineRepository implementation must honour, written
 * once: a new adapter earns its place by extending this class and providing
 * its own repository() — the same suite then proves it keeps the contract.
 *
 * The contract is deliberately silent on instance identity (an in-memory
 * adapter may hand out the same object, a persistent one a fresh hydration);
 * it speaks only of observable state across the load/save cycle.
 */
abstract class MachineRepositoryContract extends TestCase
{
    abstract protected function repository(VendingMachine $machine): MachineRepository;

    #[Test]
    public function it_loads_the_machine_it_was_given(): void
    {
        $repository = $this->repository($this->machine(drawer: CoinSet::fromCoins(Coin::TenCents)));

        $machine = $repository->load();

        self::assertTrue($machine->drawer()->equals(CoinSet::fromCoins(Coin::TenCents)));
        self::assertTrue($machine->balance()->equals(Money::zero()));
    }

    #[Test]
    public function after_save_load_observes_the_saved_state(): void
    {
        $repository = $this->repository($this->machine());

        $machine = $repository->load();
        $machine->insert(Coin::TwentyFiveCents);
        $repository->save($machine);

        self::assertTrue($repository->load()->balance()->equals(Money::fromCents(25)));
    }

    #[Test]
    public function saving_what_was_loaded_changes_nothing(): void
    {
        $repository = $this->repository($this->machine(drawer: CoinSet::fromCoins(Coin::FiveCents)));

        $repository->save($repository->load());
        $repository->save($repository->load());

        $machine = $repository->load();
        self::assertTrue($machine->drawer()->equals(CoinSet::fromCoins(Coin::FiveCents)));
        self::assertTrue($machine->balance()->equals(Money::zero()));
    }

    private function machine(?CoinSet $drawer = null): VendingMachine
    {
        return VendingMachine::assemble(
            Catalog::from(Product::of('WATER', Money::fromCents(65))),
            Inventory::empty()->withStock(Selector::fromInput('WATER'), 1),
            $drawer ?? CoinSet::empty(),
            new ChangeMaker(),
        );
    }
}
