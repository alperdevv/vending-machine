# 4. Exception taxonomy: invariant violations vs domain flow

- Status: Accepted
- Date: 2026-06-10

## Context

The machine throws for two very different reasons, and conflating them makes
error handling muddy. A caller passing something impossible (a bug) is not the
same as a legitimate business situation the application is expected to handle
(no funds, sold out, no change possible).

## Decision

Two distinct kinds of exception, separated by base type and by where they are
thrown:

- **Invariant violations (programming errors).** Impossible inputs such as a
  negative amount of money. These are bugs, never meant to be caught by type and
  recovered from — they signal code to fix. They are thrown inline using the SPL
  `LogicException` family (`InvalidArgumentException`), not wrapped in a project
  class.
- **Domain-flow exceptions (business outcomes).** Expected situations such as
  `InsufficientFunds`, `ProductSoldOut` or `CannotDispenseChange`. These are part
  of the modelled behaviour: the application layer catches them and maps them to
  a message at the boundary. They are project-owned classes living in
  `src/Domain/Exception/`, based on `RuntimeException`, each with a name of its
  own.

## Rationale

- A bug is never caught by type to recover from, so a dedicated class for a
  programming error buys little; the standard SPL type is enough and keeps the
  code lean (YAGNI).
- Reserving `src/Domain/Exception/` exclusively for domain-flow exceptions makes
  the folder mean one thing: the business situations this domain can report.
- A trap to avoid: in the SPL hierarchy `\DomainException` is itself a
  `LogicException` — it signals a bug, not a recoverable flow. So the domain-flow
  base extends `\RuntimeException`, never SPL `\DomainException`.

## Consequences

- `Money` rejects a negative amount with `InvalidArgumentException`, thrown once
  in its constructor so every construction path is guarded in a single place.
- Domain-flow exception classes are introduced as the behaviours that raise them
  are built (none exist yet); they will share a `RuntimeException`-based domain
  base.
- The application/adapter layer can catch that base type as a single surface for
  expected failures, while invariant violations surface as the bugs they are.
