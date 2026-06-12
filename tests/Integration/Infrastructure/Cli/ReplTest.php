<?php

declare(strict_types=1);

namespace Vending\Tests\Integration\Infrastructure\Cli;

use function fopen;
use function fwrite;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function rewind;
use function str_contains;
use function stream_get_contents;

use Vending\Infrastructure\Cli\Bootstrap;

final class ReplTest extends TestCase
{
    #[Test]
    public function piped_input_produces_only_the_machines_output(): void
    {
        self::assertSame("SODA\n", $this->session("1, 0.25, 0.25, GET-SODA\n"));
    }

    #[Test]
    public function lines_with_nothing_to_emit_print_nothing(): void
    {
        self::assertSame('', $this->session("0.25\n\n"));
    }

    #[Test]
    public function exit_ends_the_session_and_later_input_is_never_read(): void
    {
        self::assertSame('', $this->session("EXIT\n0.10, RETURN-COIN\n"));
    }

    #[Test]
    public function the_interactive_prompt_tracks_the_inserted_balance(): void
    {
        $output = $this->session("0.25\n0.10\nEXIT\n", interactive: true);

        self::assertTrue(str_contains($output, '[0.00] > '));
        self::assertTrue(str_contains($output, '[0.25] > '));
        self::assertTrue(str_contains($output, '[0.35] > '));
    }

    private function session(string $input, bool $interactive = false): string
    {
        $in = fopen('php://memory', 'r+');
        self::assertNotFalse($in);
        fwrite($in, $input);
        rewind($in);

        $out = fopen('php://memory', 'r+');
        self::assertNotFalse($out);

        Bootstrap::repl($in, $out, $interactive)->run();

        rewind($out);
        $contents = stream_get_contents($out);
        self::assertNotFalse($contents);

        return $contents;
    }
}
