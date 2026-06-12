<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Infrastructure;

use Vending\Application\MachineRepository;
use Vending\Domain\VendingMachine;
use Vending\Infrastructure\InMemoryMachineRepository;
use Vending\Tests\Integration\Application\MachineRepositoryContract;

final class InMemoryMachineRepositoryTest extends MachineRepositoryContract
{
    protected function repository(VendingMachine $machine): MachineRepository
    {
        return new InMemoryMachineRepository($machine);
    }
}
