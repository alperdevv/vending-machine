<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

/**
 * A service-side action arrived while the machine is selling. The drawer and
 * the stock can only be declared with the machine open for service.
 */
final class MachineNotInService extends DomainFlowException
{
    public static function forServicing(): self
    {
        return new self('Servicing actions need the machine in service mode.');
    }

    public static function nothingToEnd(): self
    {
        return new self('No service to end: the machine is not in service.');
    }
}
