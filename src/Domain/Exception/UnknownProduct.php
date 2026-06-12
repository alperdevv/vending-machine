<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

use Vending\Domain\Selector;

/**
 * A selection no product answers to. A domain flow, not a parse error: the
 * catalog is configurable, so which selectors exist is a business fact the
 * customer cannot know in advance.
 */
final class UnknownProduct extends DomainFlowException
{
    public static function withSelector(Selector $selector): self
    {
        return new self("No product answers to selector '{$selector->value()}'.");
    }
}
