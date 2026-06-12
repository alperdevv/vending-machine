<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use Vending\Domain\Coin;

/**
 * The four tokens the coin slot accepts, mapped both ways to the domain's
 * denominations.
 *
 * There is no amount parsing here on purpose: coins are a closed set, so the
 * boundary is a table, not arithmetic. Anything outside the table — "0.30",
 * garbage, an overflowing number — is null by construction, and floats never
 * enter the picture because nothing is ever computed.
 *
 * text() mirrors the slot's own vocabulary: the 100c coin reads "1" because
 * that is the token a customer drops in, not the amount it is worth — amounts
 * are MoneyText's job.
 */
final class CoinText
{
    public function parse(string $token): ?Coin
    {
        return match ($token) {
            '0.05' => Coin::FiveCents,
            '0.10' => Coin::TenCents,
            '0.25' => Coin::TwentyFiveCents,
            '1' => Coin::OneHundredCents,
            default => null,
        };
    }

    public function text(Coin $coin): string
    {
        return match ($coin) {
            Coin::FiveCents => '0.05',
            Coin::TenCents => '0.10',
            Coin::TwentyFiveCents => '0.25',
            Coin::OneHundredCents => '1',
        };
    }
}
