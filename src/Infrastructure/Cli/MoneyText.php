<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use function intdiv;
use function str_pad;

use const STR_PAD_LEFT;

use Vending\Domain\Money;

/**
 * Renders an amount of money as text — "0.65", "1.00", "1234567.89" — from
 * integer cents alone: whole units by intdiv, the remainder zero-padded.
 * Floats never appear, so neither do their rounding artefacts; the rendering
 * is exact for any amount Money can hold.
 */
final class MoneyText
{
    public function format(Money $money): string
    {
        $cents = $money->cents();

        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
