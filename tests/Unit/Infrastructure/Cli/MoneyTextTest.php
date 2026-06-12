<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Infrastructure\Cli;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Money;
use Vending\Infrastructure\Cli\MoneyText;

final class MoneyTextTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function amounts(): array
    {
        return [
            'zero' => [0, '0.00'],
            'a single cent step' => [5, '0.05'],
            'cents only' => [65, '0.65'],
            'exactly one unit' => [100, '1.00'],
            'units and cents' => [150, '1.50'],
            'a trailing zero kept' => [110, '1.10'],
            'large amount, exact' => [123456789, '1234567.89'],
        ];
    }

    #[Test]
    #[DataProvider('amounts')]
    public function it_renders_cents_as_exact_decimal_text(int $cents, string $expected): void
    {
        self::assertSame($expected, new MoneyText()->format(Money::fromCents($cents)));
    }
}
