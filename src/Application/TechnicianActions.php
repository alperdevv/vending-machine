<?php

declare(strict_types=1);

namespace Vending\Application;

use Vending\Domain\CoinSet;
use Vending\Domain\Selector;

/**
 * What a service person can do to the machine — the technician's side of the
 * actor cut (see CustomerActions for why the application splits this way).
 *
 * Same discipline: load → delegate → save, no logic. Whether an action is
 * allowed in the current mode is the aggregate's rule, not this layer's; a
 * wrong-mode call crosses here as the flow exception the domain raised.
 */
final readonly class TechnicianActions
{
    public function __construct(private MachineRepository $machines)
    {
    }

    public function beginService(): void
    {
        $machine = $this->machines->load();
        $machine->beginService();
        $this->machines->save($machine);
    }

    public function endService(): void
    {
        $machine = $this->machines->load();
        $machine->endService();
        $this->machines->save($machine);
    }

    public function replaceDrawer(CoinSet $coins): void
    {
        $machine = $this->machines->load();
        $machine->replaceDrawer($coins);
        $this->machines->save($machine);
    }

    public function setStock(Selector $selector, int $count): void
    {
        $machine = $this->machines->load();
        $machine->setStock($selector, $count);
        $this->machines->save($machine);
    }
}
