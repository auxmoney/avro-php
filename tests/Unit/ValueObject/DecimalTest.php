<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;
use Auxmoney\Avro\ValueObject\ArbitraryPrecisionInteger;
use Auxmoney\Avro\ValueObject\Decimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DecimalTest extends TestCase
{
    /**
     * @throws InvalidArgumentException
     */
    public function testFromUnscaledValueWithValidValues(): void
    {
        $decimal = Decimal::fromUnscaledValue(12345, 2);

        self::assertSame(12345, $decimal->getUnscaledValue()->toInteger());
        self::assertSame(2, $decimal->getScale());
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testFromUnscaledValueWithZeroScale(): void
    {
        $decimal = Decimal::fromUnscaledValue(123, 0);

        self::assertSame(123, $decimal->getUnscaledValue()->toInteger());
        self::assertSame(0, $decimal->getScale());
    }

    public function testFromUnscaledValueWithNegativeScaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scale must be non-negative');

        Decimal::fromUnscaledValue(123, -1);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('fromDecimalRepresentationValidProvider')]
    public function testFromDecimalRepresentationWithValidValues(string $input, string $expectedUnscaled, int $expectedScale): void
    {
        $decimal = Decimal::fromString($input);

        self::assertSame($expectedUnscaled, $decimal->getUnscaledValue()->toString());
        self::assertSame($expectedScale, $decimal->getScale());
    }

    /**
     * @return array<int, array{string, string, int}>
     */
    public static function fromDecimalRepresentationValidProvider(): array
    {
        return [
            // Integer values
            ['0', '0', 0],
            ['1', '1', 0],
            ['-1', '-1', 0],
            ['123', '123', 0],
            ['-123', '-123', 0],

            // Decimal values
            ['1.5', '15', 1],
            ['-1.5', '-15', 1],
            ['123.45', '12345', 2],
            ['-123.45', '-12345', 2],
            ['0.5', '5', 1],
            ['-0.5', '-5', 1],
            ['0.123', '123', 3],
            ['-0.123', '-123', 3],

            // Edge cases
            ['0.0', '0', 0],
            ['0.00', '0', 0],
            ['1.0', '1', 0],
            ['10.0', '10', 0],
            ['123', '123', 0],

            // Large numbers
            ['123456789.987654321', '123456789987654321', 9],
            ['-123456789.987654321', '-123456789987654321', 9],
        ];
    }

    #[DataProvider('fromDecimalRepresentationInvalidProvider')]
    public function testFromDecimalRepresentationWithInvalidValues(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid decimal format');

        Decimal::fromString($input);
    }

    /**
     * @return array<int, array{string}>
     */
    public static function fromDecimalRepresentationInvalidProvider(): array
    {
        return [[''], ['abc'], ['1.2.3'], ['1,23'], ['1e5'], ['1.23e2'], ['+123'], ['--123'], ['123-'], ['123.'], ['.123'], ['1..2']];
    }

    #[DataProvider('fromIntegerProvider')]
    public function testFromInteger(int $input, string $expectedUnscaled, int $expectedScale): void
    {
        $decimal = Decimal::fromInteger($input);

        self::assertSame($expectedUnscaled, $decimal->getUnscaledValue()->toString());
        self::assertSame($expectedScale, $decimal->getScale());
    }

    /**
     * @return array<int, array{int, string, int}>
     */
    public static function fromIntegerProvider(): array
    {
        return [
            [0, '0', 0],
            [1, '1', 0],
            [-1, '-1', 0],
            [123, '123', 0],
            [-123, '-123', 0],
            [PHP_INT_MAX, (string) PHP_INT_MAX, 0],
            [PHP_INT_MIN, (string) PHP_INT_MIN, 0],
        ];
    }

    /**
     * @return array<int, array{float, int}>
     */
    public static function fromFloatValidProvider(): array
    {
        return [[0.0, 2], [1.0, 2], [-1.0, 2], [1.5, 1], [-1.5, 1], [123.45, 2], [-123.45, 2], [0.123, 3], [3.14159, 5]];
    }

    #[DataProvider('fromFloatInvalidProvider')]
    public function testFromFloatWithInvalidValues(float $input, int $decimals): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Float value must be finite');

        Decimal::fromFloat($input, $decimals);
    }

    /**
     * @return array<int, array{float, int}>
     */
    public static function fromFloatInvalidProvider(): array
    {
        return [[INF, 2], [-INF, 2], [NAN, 2]];
    }

    #[DataProvider('fromCentsProvider')]
    public function testFromCents(int $cents, string $expectedUnscaled, int $expectedScale, string $expectedString): void
    {
        $decimal = Decimal::fromCents($cents);

        self::assertSame($expectedUnscaled, $decimal->getUnscaledValue()->toString());
        self::assertSame($expectedScale, $decimal->getScale());
        self::assertSame($expectedString, $decimal->toString());
    }

    /**
     * @return array<int, array{int, string, int, string}>
     */
    public static function fromCentsProvider(): array
    {
        return [
            [0, '0', 2, '0'],
            [1, '1', 2, '0.01'],
            [10, '10', 2, '0.1'],
            [99, '99', 2, '0.99'],
            [100, '100', 2, '1'],
            [123, '123', 2, '1.23'],
            [1000, '1000', 2, '10'],
            [12345, '12345', 2, '123.45'],
            [-1, '-1', 2, '-0.01'],
            [-10, '-10', 2, '-0.1'],
            [-99, '-99', 2, '-0.99'],
            [-100, '-100', 2, '-1'],
            [-123, '-123', 2, '-1.23'],
            [-1000, '-1000', 2, '-10'],
            [-12345, '-12345', 2, '-123.45'],
            [PHP_INT_MAX, (string) PHP_INT_MAX, 2, '92233720368547758.07'],
            [PHP_INT_MIN, (string) PHP_INT_MIN, 2, '-92233720368547758.08'],
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('toStringProvider')]
    public function testToString(string $decimalInput, string $expectedOutput): void
    {
        $decimal = Decimal::fromString($decimalInput);

        self::assertSame($expectedOutput, $decimal->toString());
        self::assertSame($expectedOutput, (string) $decimal);
    }

    /**
     * @return array<int, array{string, string}>
     */
    public static function toStringProvider(): array
    {
        return [
            // Integer values
            ['0', '0'],
            ['1', '1'],
            ['-1', '-1'],
            ['123', '123'],
            ['-123', '-123'],

            // Decimal values
            ['1.5', '1.5'],
            ['-1.5', '-1.5'],
            ['123.45', '123.45'],
            ['-123.45', '-123.45'],
            ['0.5', '0.5'],  // Leading zero should be preserved
            ['-0.5', '-0.5'], // Leading zero should be preserved
            ['0.123', '0.123'], // Leading zero should be preserved

            // Values with trailing zeros
            ['1.0', '1'],
            ['1.00', '1'],
            ['1.50', '1.5'],
            ['123.000', '123'],
            ['0.0', '0'],
            ['0.00', '0'],
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testToBytesAndFromBytes(): void
    {
        $original = Decimal::fromString('123.45');
        $bytes = $original->toBytes();
        $restored = Decimal::fromBytes($bytes, 2);

        self::assertSame($original->getUnscaledValue()->toString(), $restored->getUnscaledValue()->toString());
        self::assertSame($original->getScale(), $restored->getScale());
        self::assertSame($original->toString(), $restored->toString());
    }

    #[DataProvider('bytesRoundTripProvider')]
    public function testBytesRoundTrip(string $decimalValue): void
    {
        $original = Decimal::fromString($decimalValue);
        $bytes = $original->toBytes();
        $restored = Decimal::fromBytes($bytes, $original->getScale());

        self::assertSame($original->toString(), $restored->toString());
    }

    /**
     * @return array<int, array{string}>
     */
    public static function bytesRoundTripProvider(): array
    {
        return [
            ['0'],
            ['1'],
            ['-1'],
            ['123'],
            ['-123'],
            ['1.5'],
            ['-1.5'],
            ['123.45'],
            ['-123.45'],
            ['0.123'],
            ['0.00000000000000000000000000000000001'],
            ['-0.00000000000000000000000000000000001'],
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('withScaleProvider')]
    public function testWithScale(string $originalValue, int $newScale, string $expectedValue): void
    {
        $decimal = Decimal::fromString($originalValue);
        $scaledDecimal = $decimal->withScale($newScale);

        self::assertSame($expectedValue, $scaledDecimal->toString());
        self::assertSame($newScale, $scaledDecimal->getScale());

        // Original should be unchanged
        self::assertSame($originalValue, $decimal->toString());
    }

    /**
     * @return array<int, array{string, int, string}>
     */
    public static function withScaleProvider(): array
    {
        return [
            // Scaling up (increasing scale multiplies unscaled value)
            ['123', 2, '123'], // 123 * 10^2 / 10^2 = 123 (no visible change, but internal representation changes)
            ['123.4', 3, '123.4'], // 1234 * 10^1 / 10^3 = 123.4 (no visible change)
            ['0', 2, '0'], // 0 * 10^2 / 10^2 = 0
            ['-123', 1, '-123'], // -123 * 10^1 / 10^1 = -123

            // Scaling down (decreasing scale divides unscaled value with HALF_UP rounding)
            ['123.45', 1, '123.5'], // 12345 / 10^1 / 10^1 = 123.5 (HALF_UP rounding: 0.5 rounds up)
            ['123.45', 0, '123'], // 12345 / 10^2 / 10^0 = 123 (HALF_UP rounding: 0.45 rounds down)
            ['1.234', 2, '1.23'], // 1234 / 10^1 / 10^2 = 1.23 (HALF_UP rounding: 0.4 rounds down)

            // Same scale (should return same instance)
            ['123.45', 2, '123.45'],
        ];
    }

    public function testWithScaleSameScaleReturnsSameInstance(): void
    {
        $decimal = Decimal::fromString('123.45');
        $scaledDecimal = $decimal->withScale(2);

        self::assertSame($decimal, $scaledDecimal);
    }

    public function testWithScaleNegativeScaleThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Scale must be non-negative');

        $decimal = Decimal::fromString('123.45');
        $decimal->withScale(-1);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $decimal = Decimal::fromUnscaledValue(12345, 3);

        self::assertSame(12345, $decimal->getUnscaledValue()->toInteger());
        self::assertSame(3, $decimal->getScale());
    }

    #[DataProvider('largeNumberProvider')]
    public function testLargeNumbers(string $input, int $expectedScale, string $expectedOutput): void
    {
        $decimal = Decimal::fromString($input);

        self::assertSame($expectedScale, $decimal->getScale());
        self::assertSame($expectedOutput, $decimal->toString());
    }

    /**
     * @return array<int, array{string, int, string}>
     */
    public static function largeNumberProvider(): array
    {
        return [
            ['123456789012345678901234567890', 0, '123456789012345678901234567890'],
            ['123456789012345678901234567890.123456789', 9, '123456789012345678901234567890.123456789'],
            ['-123456789012345678901234567890.123456789', 9, '-123456789012345678901234567890.123456789'],
            ['1.000000000000000000000001', 24, '1.000000000000000000000001'],
            ['-1.000000000000000000000001', 24, '-1.000000000000000000000001'],
        ];
    }

    public function testReadonlyClass(): void
    {
        $decimal = Decimal::fromString('123.45');
        $reflection = new ReflectionClass($decimal);

        self::assertTrue($reflection->isReadOnly());
    }

    public function testFromFloatWithUnusedDecimalsParameter(): void
    {
        // The decimals parameter in fromFloat is not actually used in the current implementation
        // The method uses number_format with 17 decimals regardless of the input
        $decimal1 = Decimal::fromFloat(1.5, 1);
        $decimal2 = Decimal::fromFloat(1.5, 5);

        // Both should produce the same result since the decimals parameter is ignored
        self::assertSame($decimal1->toString(), $decimal2->toString());
        self::assertSame($decimal1->getScale(), $decimal2->getScale());
    }

    #[DataProvider('fromFloatPrecisionProvider')]
    public function testFromFloatPrecision(float $input, string $expectedOutput, int $decimals): void
    {
        $decimal = Decimal::fromFloat($input, $decimals);
        self::assertSame($expectedOutput, $decimal->toString());
    }

    /**
     * @return array<int, array{float, string, int}>
     */
    public static function fromFloatPrecisionProvider(): array
    {
        return [
            [0.0, '0', 0],
            [1.0, '1', 0],
            [1.5, '1.5', 1],
            [1.25, '1.25', 2],
            [0.1, '0.1', 1],
            [0.125, '0.125', 3],
            [-1.5, '-1.5', 1],
            [-0.125, '-0.125', 3],
            [3.141592653589793, '3.141592653589793', 15],
            [3.141592653589793, '3.1416', 4],
        ];
    }

    public function testFromBytesWithZeroScale(): void
    {
        $original = Decimal::fromString('123');
        $bytes = $original->toBytes();
        $restored = Decimal::fromBytes($bytes, 0);

        self::assertSame('123', $restored->toString());
        self::assertSame(0, $restored->getScale());
    }

    public function testFromBytesWithLargeScale(): void
    {
        $original = Decimal::fromString('1');
        $bytes = $original->toBytes();
        $restored = Decimal::fromBytes($bytes, 10);

        self::assertSame('0.0000000001', $restored->toString());
        self::assertSame(10, $restored->getScale());
    }

    public function testFromDecimalRepresentationWithLeadingZeros(): void
    {
        $decimal = Decimal::fromString('000123.45000');

        self::assertSame('12345', $decimal->getUnscaledValue()->toString());
        self::assertSame(2, $decimal->getScale());
        self::assertSame('123.45', $decimal->toString());
    }

    public function testWithScalePreservesImmutability(): void
    {
        $original = Decimal::fromString('123.45');
        $scaled = $original->withScale(3);

        // Original should be unchanged
        self::assertSame('123.45', $original->toString());
        self::assertSame(2, $original->getScale());

        // New instance should have new scale
        self::assertSame('123.45', $scaled->toString());
        self::assertSame(3, $scaled->getScale());

        // Should be different instances
        self::assertNotSame($original, $scaled);
    }

    public function testToStringWithVeryLargeNumbers(): void
    {
        $largeNumber = '999999999999999999999999999999999999999999999999.12345678901234567890123456789';
        $decimal = Decimal::fromString($largeNumber);

        $result = $decimal->toString();
        self::assertSame($largeNumber, $result);
    }

    public function testToStringWithZeroIntegerPart(): void
    {
        // Test various cases where integer part is zero
        $testCases = [
            '0' => '0',
            '0.0' => '0',
            '0.1' => '0.1',
            '0.01' => '0.01',
            '0.001' => '0.001',
            '0.1000' => '0.1', // trailing zeros removed
        ];

        foreach ($testCases as $input => $expected) {
            $decimal = Decimal::fromString((string) $input);
            self::assertSame($expected, $decimal->toString(), "Failed for input: {$input}");
        }
    }

    public function testEdgeCaseWithMaximumScale(): void
    {
        // Test with very large scale value
        $decimal = Decimal::fromUnscaledValue(1, 100);

        $result = $decimal->toString();
        self::assertTrue(str_starts_with($result, '0.'));
        self::assertSame(100, $decimal->getScale());
    }

    public function testWithScaleExtremeValues(): void
    {
        $decimal = Decimal::fromString('123.456');

        // Scale up significantly
        $scaledUp = $decimal->withScale(20);
        self::assertSame(20, $scaledUp->getScale());
        self::assertSame('123.456', $scaledUp->toString());
        self::assertSame('12345600000000000000000', $scaledUp->getUnscaledValue()->toString());

        // Scale down to zero
        $scaledDown = $decimal->withScale(0);
        self::assertSame(0, $scaledDown->getScale());
        self::assertSame('123', $scaledDown->toString()); // Should truncate
        self::assertSame('123', $scaledDown->getUnscaledValue()->toString());

        // Scale with rounding
        $scaledDown = $decimal->withScale(2);
        self::assertSame(2, $scaledDown->getScale());
        self::assertSame('123.46', $scaledDown->toString()); // Should round
        self::assertSame('12346', $scaledDown->getUnscaledValue()->toString());
    }

    public function testFromDecimalRepresentationZeroHandling(): void
    {
        // Test various representations of zero
        $zeroInputs = ['0', '0.0', '0.00', '0.000'];

        foreach ($zeroInputs as $input) {
            $decimal = Decimal::fromString($input);
            self::assertSame('0', $decimal->getUnscaledValue()->toString(), "Failed for zero input: {$input}");
            self::assertSame(0, $decimal->getScale(), "Scale failed for zero input: {$input}");
        }
    }

    public function testNegativeZeroHandling(): void
    {
        // Test that -0 is normalized to 0
        $decimal = Decimal::fromString('-0');
        self::assertSame('0', $decimal->toString());
        self::assertSame('0', $decimal->getUnscaledValue()->toString());
        self::assertFalse($decimal->getUnscaledValue()->isNegative());
    }

    public function testBytesRoundTripWithNegativeNumbers(): void
    {
        $testValues = ['-1', '-123', '-123.45', '-0.123'];

        foreach ($testValues as $value) {
            $original = Decimal::fromString($value);
            $bytes = $original->toBytes();
            $restored = Decimal::fromBytes($bytes, $original->getScale());

            self::assertSame($original->toString(), $restored->toString(), "Round-trip failed for: {$value}");
        }
    }
}
