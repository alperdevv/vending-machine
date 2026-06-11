<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Money;
use Vending\Domain\Product;

final class ProductTest extends TestCase
{
    #[Test]
    public function it_exposes_its_selector_and_price(): void
    {
        $water = Product::of('WATER', Money::fromCents(65));

        self::assertSame('WATER', $water->selector()->value());
        self::assertTrue($water->price()->equals(Money::fromCents(65)));
    }

    #[Test]
    public function it_canonicalises_its_selector(): void
    {
        $water = Product::of('  water ', Money::fromCents(65));

        self::assertSame('WATER', $water->selector()->value());
    }

    #[Test]
    public function it_rejects_a_blank_selector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::of('   ', Money::fromCents(65));
    }

    #[Test]
    public function it_rejects_a_price_of_nothing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Product::of('WATER', Money::zero());
    }

    #[Test]
    public function it_rejects_a_price_that_the_coins_cannot_settle(): void
    {
        // 67c is not a multiple of the coin granularity, so change for it could never be paid.
        $this->expectException(InvalidArgumentException::class);

        Product::of('WATER', Money::fromCents(67));
    }

    #[Test]
    public function products_are_equal_when_their_selector_and_price_match(): void
    {
        $water = Product::of('WATER', Money::fromCents(65));

        self::assertTrue($water->equals(Product::of('WATER', Money::fromCents(65))));
        self::assertFalse($water->equals(Product::of('WATER', Money::fromCents(100))));
        self::assertFalse($water->equals(Product::of('JUICE', Money::fromCents(65))));
    }
}
