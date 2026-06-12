<?php

declare(strict_types=1);

namespace Vending\Infrastructure;

use Vending\Application\MachineRepository;
use Vending\Domain\VendingMachine;

/**
 * Keeps the machine in memory for the lifetime of the process — the
 * production adapter of this delivery, not a test double: a single-process
 * CLI needs nothing more durable.
 *
 * save() stores the reference it is given. With load() handing out that same
 * instance the call can look redundant; it is the adapter that makes it so,
 * not the contract — a serialising adapter does its real work there, and the
 * callers, written against the port's full cycle, will not change.
 */
final class InMemoryMachineRepository implements MachineRepository
{
    public function __construct(private VendingMachine $machine)
    {
    }

    public function load(): VendingMachine
    {
        return $this->machine;
    }

    public function save(VendingMachine $machine): void
    {
        $this->machine = $machine;
    }
}
