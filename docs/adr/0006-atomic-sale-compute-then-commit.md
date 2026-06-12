# 6. Atomic sale: compute-then-commit

- Status: Accepted
- Date: 2026-06-12

## Context

A sale touches three pieces of the machine's state at once: the session joins
the drawer, change leaves the drawer, the inventory drops one unit. It can
also be refused for four business reasons — unknown selector, insufficient
funds, no stock, change unpayable. The machine handles money, so the one
unacceptable failure mode is a half-applied sale: an item without payment,
or payment kept without item and change (fail-closed).

Two further facts shape the design:

- Change must be paid in physical coins the machine holds, and the coins the
  customer just inserted are physically inside the machine.
- Every piece of state the sale touches (`CoinSet`, `Inventory`) is an
  immutable value; only the aggregate itself is mutable.

## Decision

`buy()` decides everything before anything moves:

1. Validations run in a fixed order — unknown selector, then funds, then
   stock, then change. The order only matters when several failures concur;
   fixing one and making it contractual through tests beats leaving it to
   chance.
2. Change is computed against the **tentative drawer** (drawer + session):
   once the sale completes those coins are the machine's money, so a customer
   can be paid change out of pieces they themselves dropped in. Without this,
   the machine refuses sales it could physically serve.
3. Only when nothing can fail anymore are the three fields assigned. A refusal
   at any step throws a named flow exception (ADR 0004) and leaves the machine
   exactly as it was.

Because the state is immutable values, the "commit" is three assignments and
there is no rollback path to write or test. The drawer subtraction can never
overdraw: the change being removed was found within that same tentative
drawer, which meets the precondition `CoinSet::subtract()` documents — by
construction, not by a repeated check.

## Alternatives considered

- **Mutate-and-rollback.** Apply each step and undo on failure. Demands an
  inverse for every mutation and a test for every failure point in every
  order; one missed path corrupts money state. All cost, no benefit over
  computing first. Rejected.
- **A Result object instead of exceptions.** Returning
  `success | failure-reason` would split expected outcomes across two
  channels, since ADR 0004 already settles business outcomes as typed flow
  exceptions with one catch surface at the boundary. Rejected for
  consistency.
- **Change computed from the drawer alone.** Simpler to reason about, but it
  refuses sales a real machine serves (exact business loss, no compensating
  gain). The tentative drawer costs one `merge()`. Rejected.

## Consequences

- The key test is behavioural: after any refusal, the observable state —
  balance, drawer, stock — is unchanged and the session is still returnable
  intact.
- The validation order is observable and therefore contractual; changing it
  later is a documented behaviour change.
- `CoinSet` gains `merge()` now, when the domain first needs it (the session
  emptying into the drawer), closing the `add(Coin)`/`subtract(self)`
  asymmetry noted when the type was built.
