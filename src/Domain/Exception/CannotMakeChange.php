<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

use Vending\Domain\Money;

/**
 * No combination of the coins on hand pays the change this sale would owe.
 * The sale is refused whole (fail-closed): the machine never keeps the excess
 * nor pays change approximately.
 */
final class CannotMakeChange extends DomainFlowException
{
    public static function forAmount(Money $due): self
    {
        return new self("The coins on hand cannot pay the {$due->cents()} cents of change this sale owes.");
    }
}
