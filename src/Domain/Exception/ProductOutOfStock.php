<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

use Vending\Domain\Product;

/**
 * The selected product exists but no units are left. The customer-facing
 * counterpart of Inventory::dispense()'s precondition: the aggregate checks
 * availability and raises this flow before the precondition could ever trip.
 */
final class ProductOutOfStock extends DomainFlowException
{
    public static function of(Product $product): self
    {
        return new self("Product '{$product->selector()->value()}' is sold out.");
    }
}
