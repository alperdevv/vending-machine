<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
use Vending\Infrastructure\Cli\CoinText;
use Vending\Infrastructure\Cli\LineProcessor;
use Vending\Infrastructure\InMemoryMachineRepository;

final class LineProcessorTest extends TestCase
{
    #[Test]
    public function brief_example_one_buy_soda_with_exact_change(): void
    {
        $cli = $this->cli(inventory: Inventory::empty()->withStock($this->selector('SODA'), 1));

        self::assertSame('SODA', $cli->process('1, 0.25, 0.25, GET-SODA'));
    }

    #[Test]
    public function brief_example_two_return_coin(): void
    {
        self::assertSame('0.10, 0.10', $this->cli()->process('0.10, 0.10, RETURN-COIN'));
    }

    #[Test]
    public function brief_example_three_buy_water_without_exact_change(): void
    {
        $cli = $this->cli(
            inventory: Inventory::empty()->withStock($this->selector('WATER'), 1),
            drawer: CoinSet::fromCoins(Coin::TwentyFiveCents, Coin::TenCents),
        );

        self::assertSame('WATER, 0.25, 0.10', $cli->process('1, GET-WATER'));
    }

    #[Test]
    public function inserting_coins_emits_nothing(): void
    {
        self::assertSame('', $this->cli()->process('0.25'));
    }

    #[Test]
    public function a_blank_line_emits_nothing(): void
    {
        self::assertSame('', $this->cli()->process('   '));
    }

    #[Test]
    public function an_unrecognised_command_reports_itself(): void
    {
        self::assertSame("Unrecognised command 'FOO'.", $this->cli()->process('FOO'));
    }

    #[Test]
    public function a_coin_the_slot_does_not_accept_is_unrecognised(): void
    {
        self::assertSame("Unrecognised command '0.30'.", $this->cli()->process('0.30'));
    }

    #[Test]
    public function an_error_stops_the_line_but_what_already_ran_stands(): void
    {
        $cli = $this->cli();

        self::assertSame("Unrecognised command 'FOO'.", $cli->process('0.25, FOO, 0.25'));
        self::assertSame('0.25', $cli->process('RETURN-COIN'));
    }

    #[Test]
    public function a_refused_sale_reports_without_the_domain_dialect_and_keeps_the_session(): void
    {
        $cli = $this->cli(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));

        self::assertSame('Not enough money inserted for that product.', $cli->process('0.25, GET-WATER'));
        self::assertSame('0.25', $cli->process('RETURN-COIN'));
    }

    #[Test]
    public function an_unpayable_change_suggests_a_way_out(): void
    {
        $cli = $this->cli(inventory: Inventory::empty()->withStock($this->selector('WATER'), 1));

        self::assertSame(
            'No change can be given for this sale; use exact payment or RETURN-COIN.',
            $cli->process('1, GET-WATER'),
        );
    }

    #[Test]
    public function an_unknown_product_speaks_the_domain_sentence(): void
    {
        self::assertSame("No product answers to selector 'COFFEE'.", $this->cli()->process('GET-COFFEE'));
    }

    #[Test]
    public function a_service_visit_through_the_grammar(): void
    {
        $cli = $this->cli();

        self::assertSame('', $cli->process('SERVICE, STOCK WATER 1, DRAWER 0.10 0.10 0.10 0.05, DONE'));
        self::assertSame('WATER, 0.10', $cli->process('0.25, 0.25, 0.25, GET-WATER'));
    }

    #[Test]
    public function stock_needs_a_count(): void
    {
        self::assertSame("'x' is not a valid stock count.", $this->cli()->process('SERVICE, STOCK WATER x'));
    }

    #[Test]
    public function the_drawer_rejects_a_coin_outside_the_slot(): void
    {
        self::assertSame("'0.30' is not an accepted coin.", $this->cli()->process('SERVICE, DRAWER 0.30'));
    }

    #[Test]
    public function servicing_while_selling_is_refused(): void
    {
        self::assertSame(
            'Servicing actions need the machine in service mode.',
            $this->cli()->process('STOCK WATER 5'),
        );
    }

    #[Test]
    public function a_customer_is_refused_during_service(): void
    {
        self::assertSame(
            'The machine is in service; customer actions resume when service ends.',
            $this->cli()->process('SERVICE, 0.10'),
        );
    }

    private function cli(?Inventory $inventory = null, ?CoinSet $drawer = null): LineProcessor
    {
        $machines = new InMemoryMachineRepository(VendingMachine::assemble(
            Catalog::from(
                Product::of('WATER', Money::fromCents(65)),
                Product::of('JUICE', Money::fromCents(100)),
                Product::of('SODA', Money::fromCents(150)),
            ),
            $inventory ?? Inventory::empty(),
            $drawer ?? CoinSet::empty(),
            new ChangeMaker(),
        ));

        return new LineProcessor(
            new CustomerActions($machines),
            new TechnicianActions($machines),
            new CoinText(),
        );
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
