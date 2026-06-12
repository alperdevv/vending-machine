<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use function array_slice;
use function count;
use function ctype_digit;
use function explode;
use function implode;
use function str_starts_with;
use function substr;
use function trim;
use function usort;

use Vending\Application\CustomerActions;
use Vending\Application\TechnicianActions;
use Vending\Domain\Coin;
use Vending\Domain\CoinSet;
use Vending\Domain\Exception\CannotMakeChange;
use Vending\Domain\Exception\DomainFlowException;
use Vending\Domain\Exception\InsufficientFunds;
use Vending\Domain\Selector;

/**
 * Speaks the machine's grammar: a line is a comma-separated sequence of
 * commands run in order, and the line's output is whatever the machine
 * emits, joined by ", " — the exact shape of the brief's examples.
 *
 *   coins        0.05 · 0.10 · 0.25 · 1
 *   customer     RETURN-COIN · GET-<SELECTOR>
 *   technician   SERVICE · STOCK <SELECTOR> <N> · DRAWER <coin> <coin> … · DONE
 *
 * An error — unreadable input or a business refusal — reports itself and
 * stops the rest of the line; what already ran stands, like the real machine
 * that has already swallowed the coin.
 *
 * Business refusals cross this layer as the domain's own sentences when those
 * already speak the customer's language; the two that talk in cents
 * (insufficient funds, change unpayable) are reworded here, because amounts
 * in minor units are the model's dialect, not the customer's.
 */
final readonly class LineProcessor
{
    public function __construct(
        private CustomerActions $customer,
        private TechnicianActions $technician,
        private CoinText $coins,
    ) {
    }

    public function process(string $line): string
    {
        $output = [];
        foreach ($this->commands($line) as $command) {
            try {
                $output = [...$output, ...$this->run($command)];
            } catch (UnrecognisedInput $unrecognised) {
                $output[] = $unrecognised->getMessage();
                break;
            } catch (DomainFlowException $refusal) {
                $output[] = $this->messageFor($refusal);
                break;
            }
        }

        return implode(', ', $output);
    }

    /**
     * @return list<string>
     */
    private function commands(string $line): array
    {
        $commands = [];
        foreach (explode(',', $line) as $piece) {
            $piece = trim($piece);
            if ($piece !== '') {
                $commands[] = $piece;
            }
        }

        return $commands;
    }

    /**
     * @return list<string> what the machine emits for this command
     */
    private function run(string $command): array
    {
        $coin = $this->coins->parse($command);
        if ($coin !== null) {
            $this->customer->insertCoin($coin);

            return [];
        }

        if ($command === 'RETURN-COIN') {
            return $this->coinTokens($this->customer->returnCoins());
        }

        if (str_starts_with($command, 'GET-') && $command !== 'GET-') {
            $vend = $this->customer->buyProduct(Selector::fromInput(substr($command, 4)));

            return [$vend->product()->selector()->value(), ...$this->coinTokens($vend->change())];
        }

        if ($command === 'SERVICE') {
            $this->technician->beginService();

            return [];
        }

        if ($command === 'DONE') {
            $this->technician->endService();

            return [];
        }

        $words = $this->words($command);
        $verb = $words[0] ?? '';

        if ($verb === 'STOCK') {
            if (count($words) !== 3) {
                throw UnrecognisedInput::command($command);
            }

            if (!ctype_digit($words[2])) {
                throw UnrecognisedInput::stockCount($words[2]);
            }

            $this->technician->setStock(Selector::fromInput($words[1]), (int) $words[2]);

            return [];
        }

        if ($verb === 'DRAWER') {
            $coins = [];
            foreach (array_slice($words, 1) as $token) {
                $coin = $this->coins->parse($token);
                if ($coin === null) {
                    throw UnrecognisedInput::coin($token);
                }

                $coins[] = $coin;
            }

            $this->technician->replaceDrawer(CoinSet::fromCoins(...$coins));

            return [];
        }

        throw UnrecognisedInput::command($command);
    }

    private function messageFor(DomainFlowException $refusal): string
    {
        return match ($refusal::class) {
            InsufficientFunds::class => 'Not enough money inserted for that product.',
            CannotMakeChange::class => 'No change can be given for this sale; use exact payment or RETURN-COIN.',
            default => $refusal->getMessage(),
        };
    }

    /**
     * @return list<string>
     */
    private function words(string $command): array
    {
        $words = [];
        foreach (explode(' ', $command) as $word) {
            if ($word !== '') {
                $words[] = $word;
            }
        }

        return $words;
    }

    /**
     * @return list<string> one token per coin, largest denomination first
     */
    private function coinTokens(CoinSet $set): array
    {
        $denominations = Coin::cases();
        usort($denominations, static fn (Coin $a, Coin $b): int => $b->value <=> $a->value);

        $tokens = [];
        foreach ($denominations as $coin) {
            for ($left = $set->countOf($coin); $left > 0; $left--) {
                $tokens[] = $this->coins->text($coin);
            }
        }

        return $tokens;
    }
}
