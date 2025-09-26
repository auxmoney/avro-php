<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\ValueObject;

use Auxmoney\Avro\ValueObject\ArbitraryPrecisionInteger;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ArbitraryPrecisionIntegerTest extends TestCase
{
    #[DataProvider('constructorValidInputProvider')]
    public function testConstructorWithBasicValues(string|int $input, string $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        self::assertSame($expected, $integer->toString());
    }

    public static function constructorValidInputProvider(): array
    {
        return [
            // Integer inputs
            [0, '0'],
            [1, '1'],
            [-1, '-1'],
            [42, '42'],
            [-42, '-42'],
            [123456789, '123456789'],
            [-123456789, '-123456789'],
            [PHP_INT_MAX, (string) PHP_INT_MAX],
            [PHP_INT_MIN, (string) PHP_INT_MIN],

            // String inputs
            ['0', '0'],
            ['1', '1'],
            ['-1', '-1'],
            ['42', '42'],
            ['-42', '-42'],
            ['123456789', '123456789'],
            ['-123456789', '-123456789'],
            ['000123', '123'],
            ['-000123', '-123'],

            // Large numbers (beyond native int range)
            ['12345678901234567890', '12345678901234567890'],
            ['-12345678901234567890', '-12345678901234567890'],
            ['999999999999999999999999999999', '999999999999999999999999999999'],
            ['-999999999999999999999999999999', '-999999999999999999999999999999'],
            
            // Leading zeros handling
            ['000', '0'],
            ['-000', '0'],
            ['00042', '42'],
            ['-00042', '-42'],
        ];
    }

    #[DataProvider('constructorInvalidInputProvider')]
    public function testConstructorWithInvalidInputs(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Value must be a valid integer string');
        
        ArbitraryPrecisionInteger::fromNumeric($input);
    }

    public static function constructorInvalidInputProvider(): array
    {
        return [
            [''],
            ['abc'],
            ['123abc'],
            ['abc123'],
            ['12.34'],
            ['-'],
            ['+'],
            ['+123'],
            ['--123'],
            ['12-34'],
            ['1 2 3'],
            ['1.0'],
            ['1e5'],
            ['0x123'],
        ];
    }

    #[DataProvider('getValueProvider')]
    public function testGetValue(string|int $input, string $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $this->assertSame($expected, $integer->toString());
    }

    public static function getValueProvider(): array
    {
        return [
            [0, '0'],
            [1, '1'],
            [-1, '-1'],
            [42, '42'],
            [-42, '-42'],
            ['12345678901234567890', '12345678901234567890'],
            ['-12345678901234567890', '-12345678901234567890'],
            ['0', '0'],
            ['000', '0'],
            ['-000', '0'],
        ];
    }

    #[DataProvider('scalePositiveProvider')]
    public function testScalePositive(string|int $input, int $exponent, string $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $shifted = $integer->shiftDecimalPosition($exponent);
        $this->assertSame($expected, $shifted->toString());
    }

    public static function scalePositiveProvider(): array
    {
        return [
            // Zero scaling
            [0, 0, '0'],
            [42, 0, '42'],
            [-42, 0, '-42'],

            // Single digit scaling
            [1, 1, '10'],
            [1, 2, '100'],
            [1, 3, '1000'],
            [42, 1, '420'],
            [42, 2, '4200'],
            [-42, 1, '-420'],
            [-42, 2, '-4200'],

            // Large scaling
            [1, 9, '1000000000'], // Test base boundary
            [1, 10, '10000000000'],
            [1, 18, '1000000000000000000'],
            [123, 5, '12300000'],
            [-123, 5, '-12300000'],

            // Zero input with positive shift
            [0, 5, '0'],
            [0, 100, '0'],

            // Large number scaling
            ['12345678901234567890', 3, '12345678901234567890000'],
            ['-12345678901234567890', 3, '-12345678901234567890000'],
        ];
    }

    #[DataProvider('scaleNegativeProvider')]
    public function testScaleNegative(string|int $input, int $exponent, string $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $shifted = $integer->shiftDecimalPosition($exponent);
        $this->assertSame($expected, $shifted->toString());
    }

    public static function scaleNegativeProvider(): array
    {
        return [
            // Single digit right scaling
            [10, -1, '1'],
            [100, -2, '1'],
            [1000, -3, '1'],
            [420, -1, '42'],
            [4200, -2, '42'],
            [-420, -1, '-42'],
            [-4200, -2, '-42'],

            // Scales that result in zero or round up with HALF_UP rounding
            [1, -1, '0'],  // 1/10 = 0.1 → rounds down to 0
            [9, -1, '1'],  // 9/10 = 0.9 → rounds up to 1  
            [99, -2, '1'], // 99/100 = 0.99 → rounds up to 1
            [999, -3, '1'], // 999/1000 = 0.999 → rounds up to 1
            [-1, -1, '0'], // -1/10 = -0.1 → rounds down to 0  
            [-9, -1, '-1'], // -9/10 = -0.9 → rounds to -1

            // Large number right scaling
            ['12345678901234567890000', -3, '12345678901234567890'],
            ['-12345678901234567890000', -3, '-12345678901234567890'],

            // Base boundary tests
            ['1000000000', -9, '1'], // Exactly one base unit
            ['10000000000', -10, '1'],

            // Zero input with negative shift
            [0, -5, '0'],
            [0, -100, '0'],

            // Partial reductions
            [12345, -2, '123'],
            [-12345, -2, '-123'],
            [12345, -4, '1'],
            [12345, -5, '0'],
        ];
    }

    public function testScaleZeroPositions(): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric(42);
        $shifted = $integer; // No scaling needed for zero positions
        
        // Should return the same instance for zero shift
        $this->assertSame($integer, $shifted);
    }

    public function testScaleZeroValue(): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric(0);
        
        // Test positive scaling (multiplication by 10^100)
        $shifted = $integer->shiftDecimalPosition(100);
        
        // Should return the same instance when shifting zero
        $this->assertSame($integer, $shifted);
        
        // Test negative scaling (division by 10^100)
        $shiftedNegative = $integer->shiftDecimalPosition(-100);
        
        // Should return the same instance when shifting zero
        $this->assertSame($integer, $shiftedNegative);
    }

    #[DataProvider('bytesProvider')]
    public function testToBytes(string $expectedHex, string $input): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $bytes = $integer->toBytes();
        $actualHex = bin2hex($bytes);
        $this->assertSame($expectedHex, $actualHex);
    }

    #[DataProvider('bytesProvider')]
    public function testFromBytes(string $hexInput, string $expectedValue): void
    {
        $bytes = hex2bin($hexInput);
        $integer = ArbitraryPrecisionInteger::fromBytes($bytes);
        $this->assertSame($expectedValue, $integer->toString());
    }

    public static function bytesProvider(): array
    {
        return [
            // Zero
            ['', '0'],
            
            // Small positive numbers
            ['01', '1'],
            ['7f', '127'],
            ['0080', '128'],
            ['00ff', '255'],
            ['0100', '256'],
            
            // Small negative numbers
            ['ff', '-1'],
            ['81', '-127'],
            ['80', '-128'],
            ['ff7f', '-129'],
            ['ff00', '-256'],
            
            // Larger numbers
            ['7fff', '32767'],
            ['008000', '32768'],
            ['8000', '-32768'],
            ['ff7fff', '-32769'],
            
            // Multi-byte numbers
            ['00ffff', '65535'],
            ['010000', '65536'],
            ['ff0000', '-65536'],
            
            // Large numbers
            ['7fffffff', '2147483647'],
            ['0080000000', '2147483648'],
            ['80000000', '-2147483648'],
            ['ff7fffffff', '-2147483649'],

            ['00ab54a98ceb1f0ad2', '12345678901234567890'],
        ];
    }

    public function testFromBytesEmptyInput(): void
    {
        $integer = ArbitraryPrecisionInteger::fromBytes('');
        $this->assertSame('0', $integer->toString());
    }

    /**
     * Test round-trip consistency: value -> toBytes -> fromBytes -> value
     */
    public function testRoundTripConsistency(): void
    {
        $testValues = [
            0, 1, -1, 127, -128, 255, -256, 32767, -32768, 65535, -65536,
            2147483647, -2147483648, '12345678901234567890', '-12345678901234567890'
        ];

        foreach ($testValues as $value) {
            $original = ArbitraryPrecisionInteger::fromNumeric($value);
            $bytes = $original->toBytes();
            $restored = ArbitraryPrecisionInteger::fromBytes($bytes);
            
            $this->assertSame(
                $original->toString(),
                $restored->toString(),
                "Round-trip failed for value: $value"
            );
        }
    }

    /**
     * Test edge cases and boundary conditions
     */
    public function testEdgeCases(): void
    {
        // Test very large numbers
        $largePositive = '999999999999999999999999999999999999999999999999999999999999';
        $integer = ArbitraryPrecisionInteger::fromNumeric($largePositive);
        $this->assertSame($largePositive, $integer->toString());

        $largeNegative = '-999999999999999999999999999999999999999999999999999999999999';
        $integer = ArbitraryPrecisionInteger::fromNumeric($largeNegative);
        $this->assertSame($largeNegative, $integer->toString());

        // Test number with many digits that cross base boundaries
        $manyDigits = '123456789012345678901234567890123456789012345678901234567890';
        $integer = ArbitraryPrecisionInteger::fromNumeric($manyDigits);
        $this->assertSame($manyDigits, $integer->toString());

        // Test shift operations on large numbers
        $shifted = $integer->shiftDecimalPosition(10);
        $expected = $manyDigits . '0000000000';
        $this->assertSame($expected, $shifted->toString());
    }

    public function testImmutability(): void
    {
        $original = ArbitraryPrecisionInteger::fromNumeric(42);
        $shifted = $original->shiftDecimalPosition(2);
        
        // Original should be unchanged
        $this->assertSame('42', $original->toString());
        $this->assertSame('4200', $shifted->toString());
        
        // Should be different instances
        $this->assertNotSame($original, $shifted);
    }

    public function testLargeScales(): void
    {
        // Test very large positive scaling
        $integer = ArbitraryPrecisionInteger::fromNumeric(1);
        $shifted = $integer->shiftDecimalPosition(100);
        $expected = '1' . str_repeat('0', 100);
        $this->assertSame($expected, $shifted->toString());

        // Test very large negative scaling on large numbers
        $large = '1' . str_repeat('0', 100);
        $integer = ArbitraryPrecisionInteger::fromNumeric($large);
        
        $shifted = $integer->shiftDecimalPosition(-99);
        $this->assertSame('10', $shifted->toString());
        
        $shifted = $integer->shiftDecimalPosition(-100);
        $this->assertSame('1', $shifted->toString());
        
        $shifted = $integer->shiftDecimalPosition(-101);
        $this->assertSame('0', $shifted->toString());
    }

    #[DataProvider('isNegativeProvider')]
    public function testIsNegative(string|int $input, bool $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $this->assertSame($expected, $integer->isNegative());
    }

    public static function isNegativeProvider(): array
    {
        return [
            // Zero cases
            [0, false],
            ['0', false],
            ['000', false],
            ['-0', false], // -0 should be normalized to 0 (not negative)

            // Positive cases
            [1, false],
            [42, false],
            [PHP_INT_MAX, false],
            ['12345678901234567890', false],
            ['999999999999999999999999999999', false],

            // Negative cases
            [-1, true],
            [-42, true],
            [PHP_INT_MIN, true],
            ['-12345678901234567890', true],
            ['-999999999999999999999999999999', true],
        ];
    }

    #[DataProvider('toAbsoluteDecimalDigitsProvider')]
    public function testToAbsoluteDecimalDigits(string|int $input, array $expectedDigits): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $digits = $integer->toAbsoluteDecimalDigits();
        $this->assertSame($expectedDigits, $digits);
    }

    public static function toAbsoluteDecimalDigitsProvider(): array
    {
        return [
            // Zero case
            [0, [0]],
            ['0', [0]],

            // Single digit cases
            [1, [1]],
            [5, [5]],
            [9, [9]],
            [-1, [1]], // Absolute value
            [-5, [5]],
            [-9, [9]],

            // Multi-digit cases
            [42, [4, 2]],
            [123, [1, 2, 3]],
            [987, [9, 8, 7]],
            [-42, [4, 2]], // Absolute value
            [-123, [1, 2, 3]],
            [-987, [9, 8, 7]],

            // Larger numbers
            [12345, [1, 2, 3, 4, 5]],
            [98765, [9, 8, 7, 6, 5]],
            [-12345, [1, 2, 3, 4, 5]], // Absolute value
            [-98765, [9, 8, 7, 6, 5]],

            // Very large numbers
            ['12345678901234567890', [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0]],
            ['-12345678901234567890', [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0]],
        ];
    }

    public function testFromNumericWithIntegerInputCallsFromInteger(): void
    {
        // This test ensures fromNumeric correctly delegates to fromInteger for int inputs
        $value = 42;
        $fromNumeric = ArbitraryPrecisionInteger::fromNumeric($value);
        $fromInteger = ArbitraryPrecisionInteger::fromInteger($value);
        
        $this->assertSame($fromInteger->toString(), $fromNumeric->toString());
        $this->assertSame($fromInteger->toBytes(), $fromNumeric->toBytes());
    }

    public function testFromNumericWithStringInputCallsFromDecimalRepresentation(): void
    {
        // This test ensures fromNumeric correctly delegates to fromDecimalRepresentation for string inputs
        $value = '12345678901234567890';
        $fromNumeric = ArbitraryPrecisionInteger::fromNumeric($value);
        $fromDecimal = ArbitraryPrecisionInteger::fromDecimalRepresentation($value);
        
        $this->assertSame($fromDecimal->toString(), $fromNumeric->toString());
        $this->assertSame($fromDecimal->toBytes(), $fromNumeric->toBytes());
    }

    public function testEmptyBytesHandling(): void
    {
        // Test that empty bytes are handled correctly in edge cases
        $zero1 = ArbitraryPrecisionInteger::fromBytes('');
        $zero2 = ArbitraryPrecisionInteger::fromInteger(0);
        
        $this->assertSame($zero2->toString(), $zero1->toString());
        $this->assertFalse($zero1->isNegative());
        $this->assertSame([0], $zero1->toAbsoluteDecimalDigits());
        
        // Scaling zero with empty bytes should return same instance
        $scaled = $zero1->shiftDecimalPosition(5);
        $this->assertSame($zero1, $scaled);
    }

    #[DataProvider('toIntegerValidProvider')]
    public function testToIntegerValid(string|int $input, int $expected): void
    {
        $integer = ArbitraryPrecisionInteger::fromNumeric($input);
        $result = $integer->toInteger();
        $this->assertSame($expected, $result);
    }

    public static function toIntegerValidProvider(): array
    {
        return [
            // Zero
            [0, 0],
            ['0', 0],

            // Small positive numbers
            [1, 1],
            [42, 42],
            [127, 127],
            [128, 128],
            [255, 255],
            [256, 256],

            // Small negative numbers
            [-1, -1],
            [-42, -42],
            [-127, -127],
            [-128, -128],
            [-129, -129],
            [-256, -256],

            // Larger numbers (within 8 bytes)
            [32767, 32767],
            [32768, 32768],
            [-32768, -32768],
            [-32769, -32769],
            [65535, 65535],
            [65536, 65536],
            [-65536, -65536],
            [2147483647, 2147483647],
            [2147483648, 2147483648],
            [-2147483648, -2147483648],
            [-2147483649, -2147483649],

            // PHP_INT boundaries
            [PHP_INT_MAX, PHP_INT_MAX],
            [PHP_INT_MIN, PHP_INT_MIN],
        ];
    }

    public function testToIntegerExceedsMaxBytes(): void
    {
        // Create a number that requires more than 8 bytes
        $largeNumber = '12345678901234567890123456789'; // Much larger than max 64-bit int
        $integer = ArbitraryPrecisionInteger::fromNumeric($largeNumber);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert to integer: number of bytes exceeds 8');
        
        $integer->toInteger();
    }

    public function testToIntegerRoundTrip(): void
    {
        // Test that fromInteger -> toInteger is consistent
        $testValues = [
            0, 1, -1, 42, -42, 127, -128, 255, -256, 32767, -32768, 65535, -65536,
            2147483647, -2147483648, PHP_INT_MAX, PHP_INT_MIN
        ];

        foreach ($testValues as $value) {
            $integer = ArbitraryPrecisionInteger::fromInteger($value);
            $result = $integer->toInteger();
            
            $this->assertSame($value, $result, "Round-trip failed for value: $value");
        }
    }

    public function testToIntegerEdgeCases(): void
    {
        // Test edge case with exactly 8 bytes
        $maxInt64 = '9223372036854775807'; // 2^63 - 1 (max signed 64-bit)
        $integer = ArbitraryPrecisionInteger::fromNumeric($maxInt64);
        $result = $integer->toInteger();
        $this->assertSame(PHP_INT_MAX, $result);

        $minInt64 = '-9223372036854775808'; // -2^63 (min signed 64-bit)
        $integer = ArbitraryPrecisionInteger::fromNumeric($minInt64);
        $result = $integer->toInteger();
        $this->assertSame(PHP_INT_MIN, $result);

        // Test number slightly larger than max 64-bit (should throw)
        $tooLargePositive = '9223372036854775808'; // 2^63
        $integer = ArbitraryPrecisionInteger::fromNumeric($tooLargePositive);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot convert to integer: number of bytes exceeds 8');
        $integer->toInteger();
    }
}