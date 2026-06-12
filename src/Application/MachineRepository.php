<?php

declare(strict_types=1);

namespace Vending\Application;

use Vending\Domain\VendingMachine;

/**
 * The application's port to wherever the machine's state lives between
 * actions: load the aggregate, act on it, save it back.
 *
 * The port belongs to this layer because persisting is a use-case concern —
 * the domain neither knows nor cares that it is stored. The contract is the
 * full cycle on purpose: an adapter whose load() hands out live references
 * makes save() look redundant, but the cycle is what a persistent adapter
 * (a file, a database) implements without a single caller changing. Omitting
 * save() would couple the contract to today's adapter — exactly what a port
 * exists to prevent.
 *
 * One machine, so no identity in the contract; findById and a MachineId
 * arrive with the second machine, not before.
 */
interface MachineRepository
{
    public function load(): VendingMachine;

    public function save(VendingMachine $machine): void;
}
