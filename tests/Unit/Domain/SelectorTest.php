<?php

declare(strict_types=1);

namespace Vending\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Vending\Domain\Selector;

final class SelectorTest extends TestCase
{
    #[Test]
    public function it_canonicalises_input_by_trimming_and_upper_casing(): void
    {
        self::assertSame('WATER', Selector::fromInput('  water ')->value());
    }

    #[Test]
    public function it_rejects_a_blank_selector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Selector::fromInput("   \t");
    }

    #[Test]
    public function selectors_are_equal_when_their_canonical_form_matches(): void
    {
        $water = Selector::fromInput('WATER');

        self::assertTrue($water->equals(Selector::fromInput(' water ')));
        self::assertFalse($water->equals(Selector::fromInput('JUICE')));
    }
}
