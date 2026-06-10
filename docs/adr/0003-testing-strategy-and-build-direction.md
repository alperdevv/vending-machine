# 3. Testing strategy and build direction

- Status: Accepted
- Date: 2026-06-10

## Context

The exercise is judged on design and testing; the requirements are given and
stable (a fixed set of coins, three products, refund, change and a service
mode); and the difficulty concentrates in the domain rules — exact money and,
above all, computing change from the coins physically left in the drawer. We
have to choose how to test and in which direction to grow the code.

## Decision

- **Classicist (Detroit) unit tests**: exercise real collaborators and assert on
  the resulting state, not on interactions. Mocks are reserved for genuine
  boundaries (a clock, a persistence port), never for domain objects.
- **Inside-out build direction**: start from the stable primitives (`Money`),
  then the coin collection, then the change algorithm, then the `VendingMachine`
  aggregate, and finally the application use cases and the adapters.
- **Double loop**: an outer acceptance test pinned to the brief's worked
  examples defines the observable behaviour; the inner unit loop drives each
  piece with red-green-refactor. The acceptance test is introduced once the
  aggregate can satisfy a full scenario.

## Rationale

- The requirements are known, so there is little need for the use cases to
  "pull" the design into existence — the usual argument for starting outside-in
  is weaker here.
- The value and the risk live in the domain. Building it first, on fully tested
  foundations made of real objects, de-risks the hardest and most heavily
  evaluated part from the start.
- Classicist tests need real collaborators. Starting from the top service would
  force mocking `Money`, the coin collection and the change calculation — the
  very logic the exercise is about — and would test interactions with fakes
  instead of real behaviour.

## Alternatives considered

- **Outside-in / London (mockist), starting from the driving port and the
  machine service.** A legitimate school: boundary-first and YAGNI-friendly, and
  strong when requirements are volatile, the domain is thin, or the risk is in
  integration/I-O. Rejected as the primary style here because it defers the
  hard, high-value domain logic and pushes mocking onto the domain objects that
  are the point of the exercise. Its strength — keeping the boundary honest — is
  retained through the outer acceptance loop.

## Consequences

- Tests are high-confidence and refactor-friendly: they bind to behaviour, not
  to call sequences, so an internal redesign does not break them.
- The boundary/API is exercised later than in a pure outside-in approach; the
  acceptance loop is the guard that the assembled whole runs the brief's
  examples.
- Every commit stays green: red-green-refactor is the authoring rhythm in the
  working tree, not a commit boundary.
