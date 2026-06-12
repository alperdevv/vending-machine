<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

use function sprintf;

use Vending\Domain\Money;
use Vending\Domain\Product;

/**
 * The session does not cover the price of the selected product.
 */
final class InsufficientFunds extends DomainFlowException
{
    public static function toBuy(Product $product, Money $inserted): self
    {
        return new self(sprintf(
            "Buying '%s' takes %d cents; %d inserted so far.",
            $product->selector()->value(),
            $product->price()->cents(),
            $inserted->cents(),
        ));
    }
}
