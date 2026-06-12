<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Application;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Application\CustomerActions;
use Vending\Application\MachineRepository;
use Vending\Application\TechnicianActions;
use Vending\Domain\Catalog;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Exception\MachineInService;
use Vending\Domain\Exception\MachineNotInService;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Product;
use Vending\Domain\Selector;
use Vending\Domain\VendingMachine;
use Vending\Infrastructure\InMemoryMachineRepository;

final class TechnicianActionsTest extends TestCase
{
    #[Test]
    public function a_service_visit_leaves_the_machine_selling_what_it_declared(): void
    {
        $machines = $this->emptyMachine();
        $technician = new TechnicianActions($machines);
        $customer = new CustomerActions($machines);

        $technician->beginService();
        $technician->setStock($this->selector('WATER'), 1);
        $technician->replaceDrawer(CoinSet::fromCoins(Coin::TenCents, Coin::TenCents, Coin::TenCents, Coin::FiveCents));
        $technician->endService();

        $customer->insertCoin(Coin::TwentyFiveCents);
        $customer->insertCoin(Coin::TwentyFiveCents);
        $customer->insertCoin(Coin::TwentyFiveCents);
        $vend = $customer->buyProduct($this->selector('WATER'));

        self::assertTrue($vend->change()->equals(CoinSet::fromCoins(Coin::TenCents)));
    }

    #[Test]
    public function servicing_while_selling_crosses_the_application_as_a_flow(): void
    {
        $technician = new TechnicianActions($this->emptyMachine());

        $this->expectException(MachineNotInService::class);

        $technician->replaceDrawer(CoinSet::empty());
    }

    #[Test]
    public function a_customer_is_refused_while_the_technician_works(): void
    {
        $machines = $this->emptyMachine();
        $technician = new TechnicianActions($machines);
        $customer = new CustomerActions($machines);

        $technician->beginService();

        $this->expectException(MachineInService::class);

        $customer->insertCoin(Coin::TenCents);
    }

    private function emptyMachine(): MachineRepository
    {
        return new InMemoryMachineRepository(VendingMachine::assemble(
            Catalog::from(Product::of('WATER', Money::fromCents(65))),
            Inventory::empty(),
            CoinSet::empty(),
            new ChangeMaker(),
        ));
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
