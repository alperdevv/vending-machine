<?php

declare(strict_types=1);

namespace Vending\Domain;

/**
 * What a completed sale hands out: the product bought and the change owed,
 * as the physical coins dispensed alongside it.
 *
 * A value returned to whoever called buy() — the one consumer a sale has.
 * Exact payment shows as an empty change set: the sale succeeded and nothing
 * is owed, which is a different fact from "no change was possible" (that sale
 * never completes).
 */
final readonly class Vend
{
    private function __construct(
        private Product $product,
        private CoinSet $change,
    ) {
    }

    public static function of(Product $product, CoinSet $change): self
    {
        return new self($product, $change);
    }

    public function product(): Product
    {
        return $this->product;
    }

    public function change(): CoinSet
    {
        return $this->change;
    }
}
