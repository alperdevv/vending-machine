# 5. Make change by backtracking over finite stock

- Status: Accepted
- Date: 2026-06-11

## Context

When a customer overpays, the machine must return the difference in **real
coins taken from a finite drawer**. This is the *bounded* coin-change problem,
not the textbook one that assumes an unlimited supply of each denomination — and
the distinction changes which algorithms are correct.

The business requirement is **completeness**: if some combination of the coins
on hand sums to the amount due, the machine must find it; only when no
combination exists may it refuse the sale. Minimising the number of coins
returned is *not* a requirement.

A greedy pick (largest coin that fits, repeat) is unsound under finite stock. The
canonical counter-example: owing 30c from a drawer of `{25x1, 10x3}`, greedy
takes the 25 and strands 5c it cannot cover, declaring failure — while
`10+10+10` was available all along.

Denominations are also asymmetric: the 100c coin is accepted as payment but must
**never** be dispensed as change.

## Decision

Compute change with a **backtracking search** over the dispensable
denominations, ordered high to low. For each denomination it tries the most
coins that fit and the stock allows, then recurses on the remainder; if that
leads nowhere it backs off one coin and tries again. Trying the largest count
first ("greedy-first") makes the search return few coins in practice and makes
its output **deterministic**, so tests can assert exact results.

It lives in a pure domain service, `ChangeMaker`, with no state or I/O:
`changeFor(Money $due, CoinSet $available): ?CoinSet`. The return type carries
the three outcomes: a `CoinSet` of coins to dispense, `CoinSet::empty()` when
payment was exact (a valid sale, *not* a failure), or `null` when no combination
fits and the sale must be refused. "Impossible" is a consultable result, not an
exception (see ADR 0004).

The one change-specific rule lives here and nowhere else: the 100c coin is
filtered out of the denominations the search may use, so `Coin` and `CoinSet`
stay denomination-agnostic.

## Alternatives considered

- **Greedy.** Incomplete under finite stock (the 30c example), which would mean
  refusing sales that were actually serviceable. Rejected on correctness.
- **Dynamic programming.** Also complete, and it additionally guarantees the
  *minimum* number of coins. But that guarantee is not a requirement; it is a
  proxy for "preserve small coins for future change", a different objective DP
  does not actually optimise either. It buys a property we do not need at the
  price of the bounded-DP layering trick (a table rebuilt per denomination to
  respect stock), which is subtler to hold and to explain. Rejected as YAGNI.
- **A `ChangeStrategy` interface with interchangeable implementations.** With a
  single correct algorithm and the choice already settled here, an interface
  with one implementer is a speculative seam. The method signature is already
  the abstraction; the interface can be extracted the day a second real strategy
  exists. Rejected for now.

## Consequences

- Completeness is guaranteed: any payable change is found; `null` means genuinely
  unpayable.
- The result is not provably minimal in coin count. Greedy-first ordering yields
  few coins in practice; if minimality ever becomes a requirement it is a change
  of implementation behind the same signature, with the validity tests untouched.
- Results are deterministic, so example-based tests assert exact coin sets, and an
  invariant sweep over a range of amounts checks the rest (exact sum, within
  stock, free of 100c) without a second reference implementation.
- Cost is bounded by the closed set of denominations (three for change) and small
  due amounts, so the worst case is a handful of nodes — microseconds. The
  exponential-in-denominations shape of backtracking is irrelevant at this size.
- The 100c asymmetry is centralised in the change policy, keeping the coin and
  coin-set models symmetric and reusable.
