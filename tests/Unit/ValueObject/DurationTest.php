<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\ValueObject;

use Auxmoney\Avro\ValueObject\Duration;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function testConstructorWithValidComponents(): void
    {
        $duration = new Duration(12, 25, 5000);

        $this->assertSame(12, $duration->months);
        $this->assertSame(25, $duration->days);
        $this->assertSame(5000, $duration->milliseconds);

    }

    public function testConstructorWithZeroComponents(): void
    {
        $duration = new Duration(0, 0, 0);

        $this->assertSame(0, $duration->months);
        $this->assertSame(0, $duration->days);
        $this->assertSame(0, $duration->milliseconds);
    }

    #[DataProvider('invalidComponentsProvider')]
    public function testConstructorWithInvalidComponents(int $months, int $days, int $milliseconds, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        new Duration($months, $days, $milliseconds);
    }

    public static function invalidComponentsProvider(): array
    {
        return [
            'negative months' => [-1, 0, 0, 'Months must be non-negative'],
            'negative days' => [0, -1, 0, 'Days must be non-negative'],
            'negative milliseconds' => [0, 0, -1, 'Milliseconds must be non-negative'],
        ];
    }



    public function testConstructorWithSpecificComponents(): void
    {
        $duration = new Duration(3, 10, 1500);

        $this->assertSame(3, $duration->months);
        $this->assertSame(10, $duration->days);
        $this->assertSame(1500, $duration->milliseconds);
    }


}