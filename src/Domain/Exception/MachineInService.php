<?php

declare(strict_types=1);

namespace Vending\Domain\Exception;

/**
 * The machine is in service mode and a selling-side action arrived. A flow,
 * not a bug: a customer can press buttons while a technician has the machine
 * open, and the technician can absent-mindedly open it twice.
 */
final class MachineInService extends DomainFlowException
{
    public static function pausingCustomerActions(): self
    {
        return new self('The machine is in service; customer actions resume when service ends.');
    }

    public static function alreadyBegun(): self
    {
        return new self('The machine is already in service.');
    }
}
