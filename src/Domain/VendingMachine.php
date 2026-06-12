<?php

declare(strict_types=1);

namespace Vending\Domain;

use Vending\Domain\Exception\CannotMakeChange;
use Vending\Domain\Exception\InsufficientFunds;
use Vending\Domain\Exception\MachineInService;
use Vending\Domain\Exception\MachineNotInService;
use Vending\Domain\Exception\ProductOutOfStock;
use Vending\Domain\Exception\UnknownProduct;

/**
 * The machine itself: the menu it sells from, the items it holds, the drawer
 * it pays change from, the coins a customer has dropped in so far (the
 * session), and the mode it operates in — selling, or opened for service.
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
 * which the session being a CoinSet rather than a balance makes possible. A
 * session survives a service cycle untouched, for the same reason: those
 * pieces are not the machine's to take.
 *
 * Service is declarative: the technician sets the drawer and the stock to
 * absolute contents, leaving the machine in a known state (ADR 0007). Customer
 * and service actions are mutually exclusive, enforced through MachineMode.
 */
final class VendingMachine
{
    private CoinSet $session;
    private MachineMode $mode = MachineMode::Selling;

    private function __construct(
        private readonly Catalog $catalog,
        private Inventory $inventory,
        private CoinSet $drawer,
        private readonly ChangeMaker $changeMaker,
    ) {
        $this->session = CoinSet::empty();
    }

    public static function assemble(
        Catalog $catalog,
        Inventory $inventory,
        CoinSet $drawer,
        ChangeMaker $changeMaker,
    ): self {
        return new self($catalog, $inventory, $drawer, $changeMaker);
    }

    public function insert(Coin $coin): void
    {
        $this->guardCustomerAction();

        $this->session = $this->session->add($coin);
    }

    public function returnCoins(): CoinSet
    {
        $this->guardCustomerAction();

        $returned = $this->session;
        $this->session = CoinSet::empty();

        return $returned;
    }

    /**
     * Sells the product behind $selector for the coins in the session.
     *
     * Everything is decided before anything moves: product looked up, funds
     * and stock checked, change computed — only then do the session, the
     * drawer and the inventory change, together. A refusal at any step throws
     * a flow exception and leaves the machine exactly as it was (ADR 0006).
     *
     * Change is computed against the drawer AND the coins just inserted: once
     * the sale completes those pieces are the machine's money, so a customer
     * can be paid change out of coins they themselves dropped in. The drawer
     * subtraction can never overdraw — the change was found within that same
     * tentative drawer, which meets subtract()'s precondition by construction.
     */
    public function buy(Selector $selector): Vend
    {
        $this->guardCustomerAction();

        $product = $this->catalog->find($selector);
        if ($product === null) {
            throw UnknownProduct::withSelector($selector);
        }

        if (!$this->balance()->isGreaterThanOrEqualTo($product->price())) {
            throw InsufficientFunds::toBuy($product, $this->balance());
        }

        if (!$this->inventory->hasStock($selector)) {
            throw ProductOutOfStock::of($product);
        }

        $due = $this->balance()->subtract($product->price());
        $tentativeDrawer = $this->drawer->merge($this->session);
        $change = $this->changeMaker->changeFor($due, $tentativeDrawer);
        if ($change === null) {
            throw CannotMakeChange::forAmount($due);
        }

        $this->inventory = $this->inventory->dispense($selector);
        $this->drawer = $tentativeDrawer->subtract($change);
        $this->session = CoinSet::empty();

        return Vend::of($product, $change);
    }

    public function beginService(): void
    {
        if ($this->mode === MachineMode::Service) {
            throw MachineInService::alreadyBegun();
        }

        $this->mode = MachineMode::Service;
    }

    public function endService(): void
    {
        if ($this->mode === MachineMode::Selling) {
            throw MachineNotInService::nothingToEnd();
        }

        $this->mode = MachineMode::Selling;
    }

    public function replaceDrawer(CoinSet $coins): void
    {
        $this->guardServiceAction();

        $this->drawer = $coins;
    }

    /**
     * Declares the absolute stock of a product (ADR 0007) — only for products
     * on the menu: the inventory tracks counts by selector and knows nothing
     * of the catalog on purpose, so tying the two together is guarded here.
     */
    public function setStock(Selector $selector, int $count): void
    {
        $this->guardServiceAction();

        if ($this->catalog->find($selector) === null) {
            throw UnknownProduct::withSelector($selector);
        }

        $this->inventory = $this->inventory->withStock($selector, $count);
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

    private function guardCustomerAction(): void
    {
        if ($this->mode !== MachineMode::Selling) {
            throw MachineInService::pausingCustomerActions();
        }
    }

    private function guardServiceAction(): void
    {
        if ($this->mode !== MachineMode::Service) {
            throw MachineNotInService::forServicing();
        }
    }
}
