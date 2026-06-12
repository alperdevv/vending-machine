<?php

declare(strict_types=1);

namespace Vending\Tests\Acceptance;

use function fopen;
use function fwrite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function rewind;
use function stream_get_contents;

use Vending\Infrastructure\Cli\Bootstrap;

/**
 * The three examples of the brief, verbatim, against the fully wired machine
 * — same composition root as bin/vending, fed by pipe. Each example is an
 * independent session on a freshly stocked machine.
 */
final class BriefExamplesTest extends TestCase
{
    #[Test]
    public function example_one_buy_soda_with_exact_change(): void
    {
        self::assertSame("SODA\n", $this->session("1, 0.25, 0.25, GET-SODA\n"));
    }

    #[Test]
    public function example_two_start_adding_money_but_ask_for_return_coin(): void
    {
        self::assertSame("0.10, 0.10\n", $this->session("0.10, 0.10, RETURN-COIN\n"));
    }

    #[Test]
    public function example_three_buy_water_without_exact_change(): void
    {
        self::assertSame("WATER, 0.25, 0.10\n", $this->session("1, GET-WATER\n"));
    }

    private function session(string $input): string
    {
        $in = fopen('php://memory', 'r+');
        self::assertNotFalse($in);
        fwrite($in, $input);
        rewind($in);

        $out = fopen('php://memory', 'r+');
        self::assertNotFalse($out);

        Bootstrap::repl($in, $out, interactive: false)->run();

        rewind($out);
        $contents = stream_get_contents($out);
        self::assertNotFalse($contents);

        return $contents;
    }
}
