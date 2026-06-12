# 9. Driving port: actor-cut action classes, no input interfaces

- Status: Accepted
- Date: 2026-06-12

## Context

The application layer needs a shape: how many classes for seven thin use
cases, and whether the driving side gets formal port interfaces the way the
driven side did (`MachineRepository`). Hexagonal literature often formalises
input ports as interfaces; this project already has a criterion for when an
interface earns its place (ADR 0005, ADR 0008: no seam before its second
client).

One more fact: the brief defines exactly two actors with mutually exclusive
actions — the customer, and the service person who opens the machine — and
the domain already enforces that split through `MachineMode`.

## Decision

Two classes, cut by actor:

- `CustomerActions` — insert a coin, buy a product, get the coins back.
- `TechnicianActions` — begin/end service, declare the drawer and the stock.

Every method is the full cycle — load, delegate to the aggregate, save — with
no business logic of its own; flow exceptions cross the layer untouched, and a
refusal propagates before `save()` is reached, so a half-done action is never
persisted whatever adapter sits behind the port.

**No input-port interfaces.** The public signatures of these two classes *are*
the driving port. The asymmetry with the driven side is the dependency rule
itself: at the output the core calls outward, so the dependency must be
inverted through an interface; at the input the driver already points inward —
there is nothing to invert, and an interface with one implementer is the
header interface this project keeps rejecting.

The actor cut is not a style preference: it is the partition the business
already has. The brief names the two actors; the domain encodes their mutual
exclusion; the application inherits the same seam.

## Alternatives considered

- **One handler class per use case** (seven invokables). Finest granularity:
  growth is a new file, cross-cutting decorates one operation. Rejected as a
  finer partition than the business asks for today — extracting a handler
  from a tested class the day one operation needs a life of its own is a
  trivial refactor, and until then seven files restate what two actors say.
- **A single application service** with seven methods. Smallest file count,
  but it merges two actors into one surface and its growth path is the god
  class. Rejected.
- **Formal driving-port interfaces** over either shape. Nothing to invert at
  the entrance; ceremony for a single CLI driver. Rejected.

## Consequences

- The CLI maps its commands per actor; a future HTTP adapter would do the
  same against the same two classes.
- Cross-cutting per operation (telemetry on sales, say) is a decorator over
  the actor class — or the moment that feels coarse, the extraction the first
  alternative describes.
- A new kind of user (an auditor, a payments processor) is a new actor class
  beside these two, touching neither.
