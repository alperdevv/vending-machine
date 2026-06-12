<?php

declare(strict_types=1);

namespace Vending\Application;

use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Selector;
use Vending\Domain\Vend;

/**
 * What a customer can do to the machine, as the application's surface: one
 * method per action, each a full load → delegate → save cycle.
 *
 * The cut is the brief's own: it names two actors with mutually exclusive
 * actions, the domain enforces that split through MachineMode, and this layer
 * inherits it — customer actions here, technician actions next door. No
 * business logic lives at this level; rules belong to the aggregate.
 *
 * Flow exceptions cross this layer untouched: mapping them to messages is the
 * boundary's job, and a refusal propagates before save() is reached, so a
 * half-done action is never persisted — whatever the adapter behind the port.
 */
final readonly class CustomerActions
{
    public function __construct(private MachineRepository $machines)
    {
    }

    public function insertCoin(Coin $coin): void
    {
        $machine = $this->machines->load();
        $machine->insert($coin);
        $this->machines->save($machine);
    }

    public function buyProduct(Selector $selector): Vend
    {
        $machine = $this->machines->load();
        $vend = $machine->buy($selector);
        $this->machines->save($machine);

        return $vend;
    }

    public function returnCoins(): CoinSet
    {
        $machine = $this->machines->load();
        $coins = $machine->returnCoins();
        $this->machines->save($machine);

        return $coins;
    }
}
