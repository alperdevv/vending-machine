# Vending Machine

A vending machine modelled as a pure domain behind a hexagonal architecture:
it takes coins (0.05, 0.10, 0.25, 1), sells items, returns the exact coins you
inserted on demand, and pays change with the physical coins in its drawer — a
sale the drawer cannot pay change for is refused whole, with your money intact.
A service person can open the machine and declare its stock and change float.

## Requirements

Docker (with Compose). Nothing else — PHP, Composer and every tool run inside
the image.

## Getting started

```bash
make build     # build the image (PHP 8.4 + pcov + Composer)
make install   # install dependencies
make stan      # static analysis (PHPStan, level max + strict rules)
make cs        # coding style check (php-cs-fixer)
make test      # run the whole test suite
make run       # start the machine
```

## Using the machine

A line is a comma-separated sequence of commands, run in order; the machine
prints whatever it emits, joined the same way. Interactively you get a prompt
with the inserted balance; piped input works too:

```
$ printf '1, 0.25, 0.25, GET-SODA\n0.10, 0.10, RETURN-COIN\n1, GET-WATER\n' | make -s run
SODA
0.10, 0.10
WATER, 0.25, 0.10
```

| Command | Meaning |
| --- | --- |
| `0.05` `0.10` `0.25` `1` | insert a coin |
| `GET-WATER` `GET-JUICE` `GET-SODA` | buy an item |
| `RETURN-COIN` | get back exactly the coins inserted |
| `SERVICE` | open the machine for servicing |
| `STOCK <SELECTOR> <N>` | declare an item's stock (in service) |
| `DRAWER <coin> <coin> …` | declare the change float (in service) |
| `DONE` | close servicing |
| `EXIT` | quit (also Ctrl-D) |

The machine starts stocked with the three items — Water 0.65, Juice 1.00,
Soda 1.50 — five units each, and ten coins of each change denomination.

## Design

The core is a pure domain (no I/O, no framework), driven by a thin
application layer and adapters around it; dependencies always point inward.
Every significant decision has an architecture decision record under
[`docs/adr/`](docs/adr/README.md), each with the alternatives weighed and
rejected. The short version:

- **Money is integer cents** wrapped in a `Money` value object; floats never
  touch an amount, not even at the CLI boundary (ADR 0002).
- **Change-making is bounded coin change**: with a finite drawer a greedy pick
  is incorrect — 30c against {25×1, 10×3} strands 5c it cannot cover — so the
  `ChangeMaker` backtracks, finding a combination whenever one exists
  (ADR 0005).
- **A sale is atomic, compute-then-commit**: everything is validated and
  computed before any state moves, so a refusal leaves no trace — there is no
  rollback because there is nothing to roll back. Change is computed against
  drawer *plus* session: the machine can pay you change out of coins you just
  inserted (ADR 0006).
- **Expected business outcomes are typed flow exceptions** (sold out,
  insufficient funds, change unpayable…), distinct from programming errors,
  and mapped to messages at the boundary (ADR 0004).
- **Service declares state** — the technician sets absolute contents (SET,
  not ADD), leaving the machine in a known state (ADR 0007).
- **The application surface is cut by actor** — `CustomerActions` and
  `TechnicianActions`, the same split the requirements and the machine's
  mode already make (ADR 0009). Domain events were weighed and deliberately
  deferred, with the trigger condition written down (ADR 0008).

## Project structure

```
src/Domain/           the rules: Money, Coin, CoinSet, Product, Catalog,
                      Inventory, ChangeMaker, the VendingMachine aggregate
src/Application/      use cases (one class per actor) + the repository port
src/Infrastructure/   adapters: in-memory repository, CLI (grammar, REPL)
bin/vending           one-line shim over the composition root
tests/                Unit · Integration · Acceptance (the brief's examples)
docs/adr/             the decision log
```

## Testing

Three suites: unit tests cover the domain with real objects (no mocks),
integration tests exercise use cases through the repository, and the
acceptance suite runs the brief's three examples verbatim against the fully
wired machine. CI runs tests, PHPStan at max level with strict rules, coding
style, and mutation testing on every push.

**Mutation testing** ([Infection](https://infection.github.io)) audits the
assertions themselves: it plants small bugs and checks the suite notices.
The domain is gated at **99% MSI**; the two surviving mutants are equivalent,
analysed and accepted:

- `CoinSet`'s `?? 0` fallback mutated to `?? -1` — it only feeds an overdraw
  check that throws identically for every reachable state, since tallies are
  strictly positive by invariant.
- `Inventory`'s drop-at-zero mutated to keep a zero count — it alters only
  the internal canonical form, invisible through `stockOf`/`hasStock`.

```bash
docker compose run --rm app vendor/bin/infection --threads=max
```

## License

Released under the [MIT License](LICENSE).
