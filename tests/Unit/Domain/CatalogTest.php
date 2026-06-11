<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Catalog;
use Vending\Domain\Money;
use Vending\Domain\Product;
use Vending\Domain\Selector;

final class CatalogTest extends TestCase
{
    #[Test]
    public function it_finds_a_product_by_its_selector(): void
    {
        $catalog = Catalog::from(
            Product::of('WATER', Money::fromCents(65)),
            Product::of('SODA', Money::fromCents(150)),
        );

        $found = $catalog->find(Selector::fromInput('WATER'));

        self::assertNotNull($found);
        self::assertTrue($found->equals(Product::of('WATER', Money::fromCents(65))));
    }

    #[Test]
    public function it_finds_a_product_however_the_selector_is_typed(): void
    {
        $catalog = Catalog::from(Product::of('WATER', Money::fromCents(65)));

        self::assertNotNull($catalog->find(Selector::fromInput(' water ')));
    }

    #[Test]
    public function it_returns_null_for_a_selector_it_does_not_carry(): void
    {
        $catalog = Catalog::from(Product::of('WATER', Money::fromCents(65)));

        self::assertNull($catalog->find(Selector::fromInput('JUICE')));
    }

    #[Test]
    public function it_rejects_a_catalog_with_no_products(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Catalog::from();
    }

    #[Test]
    public function it_rejects_two_products_with_the_same_canonical_selector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Catalog::from(
            Product::of('water', Money::fromCents(65)),
            Product::of('WATER', Money::fromCents(100)),
        );
    }
}
