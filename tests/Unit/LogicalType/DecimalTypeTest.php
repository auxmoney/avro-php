<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\DecimalType;
use Auxmoney\Avro\ValueObject\Decimal;
use PHPUnit\Framework\TestCase;

class DecimalTypeTest extends TestCase
{
    private DecimalType $decimalType;

    protected function setUp(): void
    {
        $this->decimalType = new DecimalType(10, 2);
    }

    public function testConstructWithDefaults(): void
    {
        $decimalType = new DecimalType(5);

        $this->assertSame(5, $decimalType->getPrecision());
        $this->assertSame(0, $decimalType->getScale());
        $this->assertNull($decimalType->getSize());
    }

    public function testConstructWithPrecisionAndScale(): void
    {
        $decimalType = new DecimalType(10, 3);

        $this->assertSame(10, $decimalType->getPrecision());
        $this->assertSame(3, $decimalType->getScale());
        $this->assertNull($decimalType->getSize());
    }

    public function testConstructWithPrecisionScaleAndSize(): void
    {
        $decimalType = new DecimalType(10, 3, 8);

        $this->assertSame(10, $decimalType->getPrecision());
        $this->assertSame(3, $decimalType->getScale());
        $this->assertSame(8, $decimalType->getSize());
    }

    public function testGetPrecision(): void
    {
        $this->assertSame(10, $this->decimalType->getPrecision());
    }

    public function testGetScale(): void
    {
        $this->assertSame(2, $this->decimalType->getScale());
    }

    public function testValidateWithValidDecimal(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('123.45');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->decimalType->validate($decimal, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Decimal value must be an instance of Decimal');

        $result = $this->decimalType->validate(123.45, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithString(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Decimal value must be an instance of Decimal');

        $result = $this->decimalType->validate('123.45', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithNull(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Decimal value must be an instance of Decimal');

        $result = $this->decimalType->validate(null, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('123.45');

        $result = $this->decimalType->validate($decimal, null);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatumAndNullContext(): void
    {
        $result = $this->decimalType->validate(123.45, null);

        $this->assertFalse($result);
    }

    public function testNormalizeWithDecimal(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('123.45');

        $result = $this->decimalType->normalize($decimal);

        // The result should be the bytes representation of the decimal
        // scaled to the configured precision (2 decimal places)
        $this->assertSame($decimal->withScale(2)->toBytes(), $result);
    }

    public function testNormalizeWithDifferentScale(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('123.4567'); // 4 decimal places

        $result = $this->decimalType->normalize($decimal);

        // Should be scaled down to 2 decimal places as configured
        $scaledDecimal = $decimal->withScale(2);
        $this->assertSame($scaledDecimal->toBytes(), $result);
    }

    public function testDenormalizeWithValidBytes(): void
    {
        $originalDecimal = Decimal::fromDecimalRepresentation('123.45');
        $bytes = $originalDecimal->withScale(2)->toBytes();

        $result = $this->decimalType->denormalize($bytes);

        $this->assertInstanceOf(Decimal::class, $result);
        $this->assertSame(2, $result->getScale());
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDecimal = Decimal::fromDecimalRepresentation('987.65');

        $normalized = $this->decimalType->normalize($originalDecimal);
        $denormalized = $this->decimalType->denormalize($normalized);

        // Should maintain the same value after round trip, scaled to the configured precision
        $expectedDecimal = $originalDecimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
        $this->assertSame(2, $denormalized->getScale());
    }

    public function testNormalizeAndDenormalizeWithZeroScale(): void
    {
        $decimalType = new DecimalType(5, 0);
        $originalDecimal = Decimal::fromInteger(123);

        $normalized = $decimalType->normalize($originalDecimal);
        $denormalized = $decimalType->denormalize($normalized);

        $this->assertSame($originalDecimal->toString(), $denormalized->toString());
        $this->assertSame(0, $denormalized->getScale());
    }

    public function testWithLargePrecisionAndScale(): void
    {
        $decimalType = new DecimalType(20, 10);
        $decimal = Decimal::fromDecimalRepresentation('1234567890.1234567890');

        $normalized = $decimalType->normalize($decimal);
        $denormalized = $decimalType->denormalize($normalized);

        $this->assertInstanceOf(Decimal::class, $denormalized);
        $this->assertSame(10, $denormalized->getScale());
    }

    public function testWithNegativeDecimal(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('-123.45');

        $normalized = $this->decimalType->normalize($decimal);
        $denormalized = $this->decimalType->denormalize($normalized);

        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }

    public function testFixedDecimalTypeWithValidSize(): void
    {
        $decimalType = new DecimalType(10, 2, 4); // 4 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('123.45');

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(4, strlen($normalized));

        $denormalized = $decimalType->denormalize($normalized);
        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }

    public function testFixedDecimalTypeWithSmallerValue(): void
    {
        $decimalType = new DecimalType(10, 2, 8); // 8 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('1.23'); // Small value that needs padding

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(8, strlen($normalized)); // Should be padded to 8 bytes

        $denormalized = $decimalType->denormalize($normalized);
        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }

    public function testFixedDecimalTypeWithNegativeValue(): void
    {
        $decimalType = new DecimalType(10, 2, 8); // 8 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('-1.23'); // Negative value that needs padding

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(8, strlen($normalized)); // Should be padded to 8 bytes

        // Verify padding is correct for negative numbers (should be 0xFF bytes)
        $paddingByte = ord($normalized[0]);
        $this->assertSame(0xFF, $paddingByte, 'Negative numbers should be padded with 0xFF bytes');

        $denormalized = $decimalType->denormalize($normalized);
        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }

    public function testFixedDecimalTypeWithPositiveValuePadding(): void
    {
        $decimalType = new DecimalType(10, 2, 8); // 8 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('1.23'); // Positive value that needs padding

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(8, strlen($normalized)); // Should be padded to 8 bytes

        // Verify padding is correct for positive numbers (should be 0x00 bytes)
        $paddingByte = ord($normalized[0]);
        $this->assertSame(0x00, $paddingByte, 'Positive numbers should be padded with 0x00 bytes');

        $denormalized = $decimalType->denormalize($normalized);
        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }

    public function testFixedDecimalTypeValueExceedsSize(): void
    {
        $decimalType = new DecimalType(10, 2, 2); // Only 2 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('1234567.89'); // Large value that exceeds 2 bytes
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->once())->method('addError')
            ->with('Decimal value requires 4 bytes but fixed schema only allows 2 bytes');

        $result = $decimalType->validate($decimal, $context);
        $this->assertFalse($result);
    }

    public function testFixedDecimalTypeValidationPasses(): void
    {
        $decimalType = new DecimalType(10, 2, 4); // 4 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('123.45'); // Value that fits in 4 bytes
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->never())->method('addError');

        $result = $decimalType->validate($decimal, $context);
        $this->assertTrue($result);

        // And normalize should work without throwing
        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(4, strlen($normalized));
    }

    public function testFixedDecimalTypeWithZero(): void
    {
        $decimalType = new DecimalType(10, 2, 4); // 4 bytes fixed size
        $decimal = Decimal::fromDecimalRepresentation('0.00');

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(4, strlen($normalized)); // Should be padded to 4 bytes

        // Verify zero is padded correctly
        $this->assertSame(str_repeat("\x00", 4), $normalized, 'Zero should be padded with 0x00 bytes');

        $denormalized = $decimalType->denormalize($normalized);
        $this->assertSame('0', $denormalized->toString());
    }

    public function testFixedDecimalTypeExactSize(): void
    {
        $decimalType = new DecimalType(10, 0, 4); // 4 bytes fixed size, no scale
        // Create a decimal that produces exactly 4 bytes
        $decimal = Decimal::fromInteger(16777215); // 0x00FFFFFF = 4 bytes

        $normalizedWithoutFixedSize = $decimal->toBytes();
        // Adjust the test value if needed to get exactly the right size
        if (strlen($normalizedWithoutFixedSize) !== 4) {
            $decimal = Decimal::fromInteger(2147483647); // Max 32-bit signed int
            $normalizedWithoutFixedSize = $decimal->toBytes();

            if (strlen($normalizedWithoutFixedSize) > 4) {
                $decimal = Decimal::fromInteger(8388607); // 0x7FFFFF = 3 bytes, will be padded to 4
            }
        }

        $normalized = $decimalType->normalize($decimal);
        $this->assertSame(4, strlen($normalized));

        $denormalized = $decimalType->denormalize($normalized);
        $this->assertSame($decimal->toString(), $denormalized->toString());
    }

    public function testBytesDecimalTypeUnaffectedBySize(): void
    {
        // Test that bytes type (size = null) works as before
        $decimalType = new DecimalType(10, 2); // No size specified
        $decimal = Decimal::fromDecimalRepresentation('12345.67');

        $normalized = $decimalType->normalize($decimal);

        // Should not be fixed size - length can vary
        $this->assertGreaterThan(0, strlen($normalized));

        $denormalized = $decimalType->denormalize($normalized);
        $expectedDecimal = $decimal->withScale(2);
        $this->assertSame($expectedDecimal->toString(), $denormalized->toString());
    }
}
