<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use RuntimeException;

/**
 * The boundary's own refusal: input it cannot read never reaches the domain.
 *
 * Deliberately not a domain exception — the asymmetry is the same one the
 * model draws: a selector nobody answers to is a business fact (the domain's
 * UnknownProduct), while a token the grammar cannot read is a parse problem,
 * settled right here at the edge.
 */
final class UnrecognisedInput extends RuntimeException
{
    public static function command(string $raw): self
    {
        return new self("Unrecognised command '{$raw}'.");
    }

    public static function coin(string $token): self
    {
        return new self("'{$token}' is not an accepted coin.");
    }

    public static function stockCount(string $raw): self
    {
        return new self("'{$raw}' is not a valid stock count.");
    }
}
