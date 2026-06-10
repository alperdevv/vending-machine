# 1. Hexagonal architecture with tactical DDD

- Status: Accepted
- Date: 2026-06-10

## Context

The exercise is judged on *how* the machine is built — architecture,
maintainability, extensibility, testing and domain modelling — rather than on
whether it runs, and every decision has to be defended. Two facts shape the
design:

- The delivery mechanism (CLI, HTTP, …) is deliberately left open, so the core
  must not depend on any of them.
- The interesting part is the business rules, not I/O: money must be exact,
  change is constrained by the coins physically in the drawer, inserted coins
  are physical pieces rather than a balance, and the machine must stay
  consistent under failure (fail-closed).

## Decision

Adopt **hexagonal architecture (ports & adapters)** with **tactical DDD** in
the core:

- **Domain** — pure, with no I/O and no framework. Value objects (`Money`,
  `Coin`, the coin collection), entities and the `VendingMachine` aggregate
  that guards its invariants, domain services (the change strategy) and named
  domain exceptions.
- **Application** — thin use cases (insert coin, buy, refund, service) that
  load the aggregate, delegate to it and persist. No business logic of their
  own.
- **Infrastructure** — adapters: a CLI driver and an in-memory repository
  behind an interface.

Dependencies always point inward: infrastructure depends on the domain, never
the other way round.

## Alternatives considered

- **Transaction script / anemic model.** A handful of procedural services over
  primitive arrays and integers. Quickest to write, but the rules (exact money,
  bounded-inventory change, fail-closed atomicity) would be scattered and
  duplicated across services and hard to test in isolation. The brief
  explicitly warns against under-engineering. Rejected.
- **Active-record / framework-centric model.** Couples the domain to
  persistence and a framework, which fights both the "interface left open"
  requirement and pure-domain testing. Rejected.
- **Full CQRS with a command bus and event sourcing.** Defensible at scale, but
  for a single small aggregate it is the *opposite* failure mode the brief
  warns about — over-engineering whose indirection buys nothing here. Recorded
  as the direction to take only if the system grew substantially. Rejected for
  now.

## Consequences

- The domain is testable with real objects, no mocks and no I/O — which is what
  the change algorithm and money rules need.
- New adapters (HTTP today, a queue tomorrow) can be added without touching the
  core.
- Each decision maps to a named artifact that can be pointed at in the defence.
- The cost is more files and indirection than a script would need; this is
  justified by the evaluation criteria and the richness of the rules, not by
  the current line count. The inward dependency direction must be kept honest —
  later enforced with static analysis.
