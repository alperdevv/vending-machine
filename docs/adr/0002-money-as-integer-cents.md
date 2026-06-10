# 2. Represent money as integer minor units

- Status: Accepted
- Date: 2026-06-10

## Context

The machine adds up inserted coins, compares the total against a price and
computes change. All of this arithmetic has to be exact: for this exercise a
rounding error in money is an automatic fail.

IEEE-754 floating point cannot represent most decimal money values exactly. The
canonical trap is that `0.10 + 0.25 + 0.25` does not equal `0.60` once the
values are stored as `float`.

## Decision

Every monetary amount is a **non-negative integer number of minor units
(cents)**, wrapped in a `Money` value object. No `float` ever enters the domain.

Decimals only exist at the system boundary — parsing `"0.25"` from input,
formatting an amount for display — and even there they are handled with
string/integer manipulation, never `floatval` or `number_format($cents / 100)`.
The boundary is "float-blind" by rule.

A `Money` instance is immutable: arithmetic returns a new `Money`, and an
operation that would produce a negative amount is rejected (fail-closed) rather
than silently clamped. A non-negative amount is an invariant, so that rejection
is an invariant violation rather than a business outcome (see ADR 0004).

## Alternatives considered

- **`float` / `double`.** Rejected outright: representation error makes
  accumulation and equality unsound, and a money bug is disqualifying.
- **A decimal / BCMath library.** Correct, but a heavier dependency and API for
  a domain whose smallest unit is a fixed cent. Integer cents are exact,
  dependency-free, fast and trivially comparable. Overkill, rejected.
- **Money as a string.** Exact, but awkward to add and compare. Rejected.

## Consequences

- Arithmetic is exact, equality and ordering are plain integer comparisons, and
  there are no dependencies.
- Coin denominations are literally their cent values (5, 10, 25, 100), so `Coin`
  and `Money` line up naturally.
- The boundary (parser and formatter) must be guarded to stay float-blind; it is
  treated as a documented rule and tested at the edges.
- It assumes a single currency with a fixed minor unit, which is appropriate for
  a vending machine. A multi-currency system would pair the amount with a
  currency; recorded as a known limitation, not a current need.
