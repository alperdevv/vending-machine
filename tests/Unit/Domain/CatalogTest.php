<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Catalog;
use Vending\Domain\Money;
use Vending\Domain\Product;

final class CatalogTest extends TestCase
{
    #[Test]
    public function it_finds_a_product_by_its_selector(): void
    {
        $catalog = Catalog::from(
            Product::of('WATER', Money::fromCents(65)),
            Product::of('SODA', Money::fromCents(150)),
        );

        $found = $catalog->find('WATER');

        self::assertNotNull($found);
        self::assertTrue($found->equals(Product::of('WATER', Money::fromCents(65))));
    }

    #[Test]
    public function it_returns_null_for_a_selector_it_does_not_carry(): void
    {
        $catalog = Catalog::from(Product::of('WATER', Money::fromCents(65)));

        self::assertNull($catalog->find('JUICE'));
    }

    #[Test]
    public function it_rejects_a_catalog_with_no_products(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Catalog::from();
    }

    #[Test]
    public function it_rejects_two_products_sharing_a_selector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Catalog::from(
            Product::of('WATER', Money::fromCents(65)),
            Product::of('WATER', Money::fromCents(100)),
        );
    }
}
