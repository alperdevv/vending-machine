<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Coin;
use Vending\Infrastructure\Cli\CoinText;

final class CoinTextTest extends TestCase
{
    /**
     * @return array<string, array{string, Coin}>
     */
    public static function tokens(): array
    {
        return [
            'five cents' => ['0.05', Coin::FiveCents],
            'ten cents' => ['0.10', Coin::TenCents],
            'twenty-five cents' => ['0.25', Coin::TwentyFiveCents],
            'one unit' => ['1', Coin::OneHundredCents],
        ];
    }

    #[Test]
    #[DataProvider('tokens')]
    public function it_parses_each_accepted_token(string $token, Coin $coin): void
    {
        self::assertSame($coin, new CoinText()->parse($token));
    }

    #[Test]
    #[DataProvider('tokens')]
    public function it_renders_each_coin_as_its_slot_token(string $token, Coin $coin): void
    {
        self::assertSame($token, new CoinText()->text($coin));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rejectedTokens(): array
    {
        return [
            'an amount that is not a coin' => ['0.30'],
            'the hundred written as an amount' => ['1.00'],
            'a denomination that does not exist' => ['0.01'],
            'garbage' => ['abc'],
            'empty' => [''],
            'a number too large to be money' => ['99999999999999999999'],
            'a negative coin' => ['-0.05'],
            'whitespace around a valid token' => [' 0.05 '],
        ];
    }

    #[Test]
    #[DataProvider('rejectedTokens')]
    public function anything_outside_the_table_is_not_a_coin(string $token): void
    {
        self::assertNull(new CoinText()->parse($token));
    }
}
