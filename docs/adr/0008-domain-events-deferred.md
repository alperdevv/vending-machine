# 8. Domain events: deferred until a second reactive consumer exists

- Status: Rejected
- Date: 2026-06-12

## Context

The aggregate's actions are natural facts to announce — a product vended,
coins returned, the machine serviced — and domain events are the canonical
seam for reacting to them without touching the rules: the aggregate records,
the application publishes, subscribers react. With extensibility an explicit
evaluation axis, recording events here was seriously considered, including a
minimal real subscriber (an operational log) so the events would not hang
unconsumed.

## Decision

No domain events — for now, and for a reason that is written down rather than
implied.

Every outcome of every action already has exactly one consumer: the caller.
A sale answers with `Vend` (product + change), a refund with the returned
`CoinSet`, a refusal with a named flow exception the boundary maps to a
message. There is no second party reacting to these facts, and the one
candidate consumer contemplated — a log subscriber — would have existed *to
justify the events*, not the other way around.

This project already has a rule for that shape of decision: `ChangeMaker` has
no `ChangeStrategy` interface because a seam with a single implementation is
speculation, extracted the day the second implementation is real (ADR 0005).
Events with a manufactured consumer would break exactly that rule. One
criterion, applied on both sides: **no seam before its second client.**

**Trigger condition** — this record flips the day a second *reactive*
consumer is real: telemetry actually wanted, notifications, an audit feed,
any business rule that reacts to a sale rather than taking part in it. The
shape is already known: the aggregate records facts in past tense
(`ProductVended`…), `releaseEvents()` hands them over, the application
publishes after persisting, subscribers live in infrastructure. Until then,
cross-cutting needs (logging, metrics) sit naturally in the application
layer, wrapping the use cases, with the domain and the CLI untouched.

## Alternatives considered

- **Record events now, with a minimal log subscriber.** Executable answer to
  "how would you add telemetry?", but the subscriber is decoration: nothing
  needs it, so it is ~five production classes whose only job is to exist.
  Inconsistent with the ADR 0005 criterion. Rejected.
- **Record events with no consumer at all.** Worse: an extension story that
  has never run is a promise, not a seam. Rejected outright.

## Consequences

- The extensibility question is answered in two steps instead of one class:
  application-layer wrapping today, events at the written trigger tomorrow.
- The aggregate's public surface stays exactly as large as its behaviour.
- If the trigger fires, introducing events is additive — record, release,
  publish — and changes no existing rule or signature except `buy()` callers
  that want the richer story.
