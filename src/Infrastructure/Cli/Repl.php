<?php

declare(strict_types=1);

namespace Vending\Infrastructure\Cli;

use function fgets;
use function fwrite;
use function rtrim;
use function trim;

use Vending\Application\CustomerActions;

/**
 * The read-eval-print loop: one line in, one line out, until EOF or EXIT.
 *
 * When interactive it prompts with the inserted balance — "[0.35] > " — so
 * the brief's "currently inserted money" is visible while typing; when fed
 * through a pipe it stays silent and emits exactly the machine's output,
 * which is what the acceptance examples compare against byte by byte.
 */
final readonly class Repl
{
    /**
     * @param resource $input
     * @param resource $output
     */
    public function __construct(
        private LineProcessor $processor,
        private CustomerActions $customer,
        private MoneyText $amounts,
        private mixed $input,
        private mixed $output,
        private bool $interactive,
    ) {
    }

    public function run(): int
    {
        while (true) {
            $this->prompt();

            $line = fgets($this->input);
            if ($line === false || trim($line) === 'EXIT') {
                break;
            }

            $emitted = $this->processor->process(rtrim($line, "\r\n"));
            if ($emitted !== '') {
                fwrite($this->output, $emitted . "\n");
            }
        }

        if ($this->interactive) {
            fwrite($this->output, "\n");
        }

        return 0;
    }

    private function prompt(): void
    {
        if (!$this->interactive) {
            return;
        }

        fwrite($this->output, '[' . $this->amounts->format($this->customer->balance()) . '] > ');
    }
}
