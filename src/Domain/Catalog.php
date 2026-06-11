<?php

declare(strict_types=1);

namespace Vending\Domain;

use InvalidArgumentException;

/**
 * The set of products a machine offers, looked up by selector.
 *
 * Immutable: a machine's menu is fixed when the machine is assembled. Building
 * one is fail-fast — a machine with no products, or two products answering to
 * the same selector, is a misconfiguration and cannot be constructed.
 *
 * find() returns the product a selector maps to, or null when the selector is
 * not on the menu. What an unknown selection means for a sale is the caller's
 * decision, so the lookup stays a plain query and raises nothing.
 */
final readonly class Catalog
{
    /**
     * @param array<string, Product> $bySelector
     */
    private function __construct(private array $bySelector)
    {
    }

    public static function from(Product ...$products): self
    {
        if ($products === []) {
            throw new InvalidArgumentException('A catalog must offer at least one product.');
        }

        $bySelector = [];
        foreach ($products as $product) {
            $key = $product->selector()->value();
            if (isset($bySelector[$key])) {
                throw new InvalidArgumentException("Two products share the selector '{$key}'.");
            }

            $bySelector[$key] = $product;
        }

        return new self($bySelector);
    }

    public function find(Selector $selector): ?Product
    {
        return $this->bySelector[$selector->value()] ?? null;
    }
}
