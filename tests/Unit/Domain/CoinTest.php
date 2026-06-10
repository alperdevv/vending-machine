<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Coin;
use Vending\Domain\Money;

final class CoinTest extends TestCase
{
    #[Test]
    public function the_accepted_denominations_are_fixed(): void
    {
        self::assertSame(
            [Coin::FiveCents, Coin::TenCents, Coin::TwentyFiveCents, Coin::OneHundredCents],
            Coin::cases(),
        );
    }

    #[Test]
    #[DataProvider('denominations')]
    public function it_is_worth_its_face_value_in_cents(Coin $coin, int $cents): void
    {
        self::assertSame($cents, $coin->value);
        self::assertTrue($coin->amount()->equals(Money::fromCents($cents)));
    }

    /**
     * @return array<string, array{Coin, int}>
     */
    public static function denominations(): array
    {
        return [
            'five cents' => [Coin::FiveCents, 5],
            'ten cents' => [Coin::TenCents, 10],
            'twenty-five cents' => [Coin::TwentyFiveCents, 25],
            'one hundred cents' => [Coin::OneHundredCents, 100],
        ];
    }
}
