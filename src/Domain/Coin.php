<?php

declare(strict_types=1);

namespace Vending\Domain;

/**
 * The coins the machine accepts: a closed set of denominations, in cents.
 *
 * A backed enum (rather than an int-wrapping value object) makes an invalid
 * coin unrepresentable — there is no way to build a denomination outside this
 * set. amount() exposes a coin's worth as Money.
 */
enum Coin: int
{
    case FiveCents = 5;
    case TenCents = 10;
    case TwentyFiveCents = 25;
    case OneHundredCents = 100;

    public function amount(): Money
    {
        return Money::fromCents($this->value);
    }
}
