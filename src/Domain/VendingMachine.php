<?php

declare(strict_types=1);

namespace Vending\Domain;

/**
 * The machine itself: the items it holds, the drawer it pays change from, and
 * the coins a customer has dropped in so far (the session).
 *
 * This is the aggregate root and the one mutable object in the model. Identity
 * and lifecycle live here; every value it holds is immutable. That split is
 * what keeps each action atomic: an action computes its next values first and
 * assigns them only once nothing can fail, so a refused action leaves no trace
 * — not because anything was rolled back, but because nothing was touched.
 *
 * The session is held apart from the drawer: inserted coins are the customer's
 * pieces in escrow, not the machine's money. returnCoins() hands back exactly
 * the pieces inserted — never an equivalent amount in other denominations —
 * which the session being a CoinSet rather than a balance makes possible.
 */
final class VendingMachine
{
    private CoinSet $session;

    private function __construct(
        private Inventory $inventory,
        private CoinSet $drawer,
    ) {
        $this->session = CoinSet::empty();
    }

    public static function assemble(Inventory $inventory, CoinSet $drawer): self
    {
        return new self($inventory, $drawer);
    }

    public function insert(Coin $coin): void
    {
        $this->session = $this->session->add($coin);
    }

    public function returnCoins(): CoinSet
    {
        $returned = $this->session;
        $this->session = CoinSet::empty();

        return $returned;
    }

    public function balance(): Money
    {
        return $this->session->total();
    }

    public function drawer(): CoinSet
    {
        return $this->drawer;
    }

    public function stockOf(Selector $selector): int
    {
        return $this->inventory->stockOf($selector);
    }
}
