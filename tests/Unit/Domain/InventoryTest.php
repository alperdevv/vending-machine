<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Inventory;
use Vending\Domain\Selector;

final class InventoryTest extends TestCase
{
    #[Test]
    public function it_reports_no_stock_for_a_selector_it_does_not_track(): void
    {
        $inventory = Inventory::empty();

        self::assertSame(0, $inventory->stockOf($this->selector('WATER')));
        self::assertFalse($inventory->hasStock($this->selector('WATER')));
    }

    #[Test]
    public function setting_stock_makes_it_available(): void
    {
        $inventory = Inventory::empty()->withStock($this->selector('WATER'), 3);

        self::assertSame(3, $inventory->stockOf($this->selector('WATER')));
        self::assertTrue($inventory->hasStock($this->selector('WATER')));
    }

    #[Test]
    public function it_tracks_each_selector_independently(): void
    {
        $inventory = Inventory::empty()
            ->withStock($this->selector('WATER'), 3)
            ->withStock($this->selector('SODA'), 1);

        self::assertSame(3, $inventory->stockOf($this->selector('WATER')));
        self::assertSame(1, $inventory->stockOf($this->selector('SODA')));
        self::assertSame(0, $inventory->stockOf($this->selector('JUICE')));
    }

    #[Test]
    public function setting_stock_replaces_the_previous_count(): void
    {
        $inventory = Inventory::empty()
            ->withStock($this->selector('WATER'), 3)
            ->withStock($this->selector('WATER'), 5);

        self::assertSame(5, $inventory->stockOf($this->selector('WATER')));
    }

    #[Test]
    public function setting_stock_returns_a_new_inventory_and_leaves_the_original_untouched(): void
    {
        $base = Inventory::empty()->withStock($this->selector('WATER'), 2);

        $restocked = $base->withStock($this->selector('WATER'), 5);

        self::assertSame(2, $base->stockOf($this->selector('WATER')));
        self::assertSame(5, $restocked->stockOf($this->selector('WATER')));
    }

    #[Test]
    public function dispensing_removes_one_unit_and_leaves_the_original_untouched(): void
    {
        $two = Inventory::empty()->withStock($this->selector('WATER'), 2);

        $one = $two->dispense($this->selector('WATER'));

        self::assertSame(1, $one->stockOf($this->selector('WATER')));
        self::assertSame(2, $two->stockOf($this->selector('WATER')));
    }

    #[Test]
    public function dispensing_the_last_unit_clears_the_selector(): void
    {
        $inventory = Inventory::empty()
            ->withStock($this->selector('WATER'), 1)
            ->dispense($this->selector('WATER'));

        self::assertSame(0, $inventory->stockOf($this->selector('WATER')));
        self::assertFalse($inventory->hasStock($this->selector('WATER')));
    }

    #[Test]
    public function dispensing_with_no_stock_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Inventory::empty()->dispense($this->selector('WATER'));
    }

    #[Test]
    public function setting_a_negative_stock_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Inventory::empty()->withStock($this->selector('WATER'), -1);
    }

    #[Test]
    public function setting_stock_to_zero_clears_the_selector(): void
    {
        $inventory = Inventory::empty()
            ->withStock($this->selector('WATER'), 3)
            ->withStock($this->selector('WATER'), 0);

        self::assertSame(0, $inventory->stockOf($this->selector('WATER')));
        self::assertFalse($inventory->hasStock($this->selector('WATER')));
    }

    private function selector(string $value): Selector
    {
        return Selector::fromInput($value);
    }
}
