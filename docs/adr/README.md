# Architecture Decision Records

This directory records the significant decisions taken while building the
vending machine, in the order they were made. A record is immutable once
accepted: if a decision is later revised, a new record supersedes it rather
than rewriting the old one.

The format is intentionally light — context, the decision, the alternatives
that were weighed and rejected, and the consequences we accept — so each
record reads in a minute and stands on its own.

| #                                                    | Decision                                      |
| ---------------------------------------------------- | --------------------------------------------- |
| [0001](0001-hexagonal-architecture-and-ddd.md)       | Hexagonal architecture with tactical DDD      |
| [0002](0002-money-as-integer-cents.md)               | Represent money as integer minor units        |
| [0003](0003-testing-strategy-and-build-direction.md) | Testing strategy and build direction          |
| [0004](0004-exception-taxonomy.md)                   | Exception taxonomy: invariants vs domain flow |
| [0005](0005-change-making-by-backtracking.md)       | Make change by backtracking over finite stock |
| [0006](0006-atomic-sale-compute-then-commit.md)      | Atomic sale: compute-then-commit              |
| [0007](0007-service-declares-state-set-not-add.md)   | Service declares state: SET, not ADD          |
