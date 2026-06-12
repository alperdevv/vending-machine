# 7. Service declares state: SET, not ADD

- Status: Accepted
- Date: 2026-06-12

## Context

The brief gives the service person one job: "opens the machine and **set** the
available change and how many items we have". Two readings are possible:

- **SET** — the technician declares the absolute contents: this drawer, this
  many units. What was there before is irrelevant.
- **ADD** — the technician replenishes: "ten more cans, twenty more dimes",
  deltas applied on top of the current state.

## Decision

SET, throughout: `replaceDrawer()` swaps the whole drawer, `setStock()`
declares a product's absolute count.

- It is what the brief's own verb says.
- It leaves the machine in a **known state**: the technician counts what they
  loaded and declares it, instead of trusting that the machine's idea of its
  previous contents matches the physical truth. Declarative beats incremental
  exactly where drift is possible.
- It is idempotent — a repeated or retried service action converges instead of
  compounding — which also keeps every test a one-liner to reason about.
- `Inventory::withStock()` was already built with SET semantics when stock
  tracking was introduced; the aggregate composes it rather than fighting it.

The asymmetry with the customer side is deliberate: a customer *mutates*
state through guarded flows (a sale, a refund); a technician *declares* it.

## Alternatives considered

- **ADD (deltas).** Models the restocking gesture, but every service visit
  then depends on the machine's prior state being right, errors compound, and
  the operation is not idempotent. Rejected.
- **Both verbs.** Premature surface area with no second consumer; an additive
  flow, if a real driver ever wants one, composes at the boundary from a read
  plus a SET ("current + crate = declare"). SET cannot be rebuilt on top of
  ADD as safely, which is why SET is the primitive to keep. Rejected for now.

## Consequences

- Service actions need no knowledge of history; tests declare a state and
  assert on it directly.
- A service visit that means to top up must read first — the boundary's job,
  not the domain's.
- Stock can only be declared for products on the menu: the inventory tracks
  plain selector counts on purpose, so the aggregate guards the catalog
  membership (`UnknownProduct` otherwise).
