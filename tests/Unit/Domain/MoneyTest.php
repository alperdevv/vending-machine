<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Money;

final class MoneyTest extends TestCase
{
    #[Test]
    public function it_exposes_the_amount_in_cents_it_was_created_with(): void
    {
        $money = Money::fromCents(65);

        self::assertSame(65, $money->cents());
    }

    #[Test]
    public function zero_is_an_amount_of_no_cents(): void
    {
        self::assertSame(0, Money::zero()->cents());
    }

    #[Test]
    public function amounts_with_the_same_cents_are_equal(): void
    {
        self::assertTrue(Money::fromCents(150)->equals(Money::fromCents(150)));
        self::assertFalse(Money::fromCents(150)->equals(Money::fromCents(151)));
    }

    #[Test]
    public function it_rejects_a_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(-1);
    }

    #[Test]
    public function it_adds_two_amounts(): void
    {
        $sum = Money::fromCents(65)->add(Money::fromCents(35));

        self::assertTrue($sum->equals(Money::fromCents(100)));
    }

    #[Test]
    public function it_subtracts_a_smaller_amount(): void
    {
        $remainder = Money::fromCents(100)->subtract(Money::fromCents(65));

        self::assertTrue($remainder->equals(Money::fromCents(35)));
    }

    #[Test]
    public function subtracting_more_than_available_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCents(65)->subtract(Money::fromCents(100));
    }

    #[Test]
    public function it_orders_amounts_by_value(): void
    {
        self::assertTrue(Money::fromCents(100)->isGreaterThanOrEqualTo(Money::fromCents(100)));
        self::assertTrue(Money::fromCents(150)->isGreaterThanOrEqualTo(Money::fromCents(100)));
        self::assertFalse(Money::fromCents(50)->isGreaterThanOrEqualTo(Money::fromCents(100)));
    }
}
