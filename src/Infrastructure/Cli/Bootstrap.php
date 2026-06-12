<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use Vending\Application\CustomerActions;
use Vending\Application\TechnicianActions;
use Vending\Domain\Catalog;
use Vending\Domain\ChangeMaker;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Inventory;
use Vending\Domain\Money;
use Vending\Domain\Product;
use Vending\Domain\Selector;
use Vending\Domain\VendingMachine;
use Vending\Infrastructure\InMemoryMachineRepository;

/**
 * The composition root: the one place where adapters are constructed and
 * everything is wired by hand — ten objects need no container, the wiring
 * below IS the container. bin/vending stays a one-line shim so all of this
 * sits under static analysis and the test suites.
 *
 * The machine ships configured with the brief's three products, five units
 * of each, and a change float of ten coins per dispensable denomination —
 * enough for the brief's third example to pay 0.35 as 0.25 + 0.10.
 */
final class Bootstrap
{
    /**
     * @param resource $input
     * @param resource $output
     */
    public static function repl(mixed $input, mixed $output, bool $interactive): Repl
    {
        $machines = new InMemoryMachineRepository(self::machine());
        $customer = new CustomerActions($machines);

        return new Repl(
            new LineProcessor($customer, new TechnicianActions($machines), new CoinText()),
            $customer,
            new MoneyText(),
            $input,
            $output,
            $interactive,
        );
    }

    private static function machine(): VendingMachine
    {
        return VendingMachine::assemble(
            Catalog::from(
                Product::of('WATER', Money::fromCents(65)),
                Product::of('JUICE', Money::fromCents(100)),
                Product::of('SODA', Money::fromCents(150)),
            ),
            Inventory::empty()
                ->withStock(Selector::fromInput('WATER'), 5)
                ->withStock(Selector::fromInput('JUICE'), 5)
                ->withStock(Selector::fromInput('SODA'), 5),
            self::changeFloat(),
            new ChangeMaker(),
        );
    }

    private static function changeFloat(): CoinSet
    {
        $coins = [];
        foreach ([Coin::FiveCents, Coin::TenCents, Coin::TwentyFiveCents] as $denomination) {
            for ($i = 0; $i < 10; $i++) {
                $coins[] = $denomination;
            }
        }

        return CoinSet::fromCoins(...$coins);
    }
}
