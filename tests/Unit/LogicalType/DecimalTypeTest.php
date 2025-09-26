<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\DecimalType;
use Auxmoney\Avro\ValueObject\Decimal;
use InvalidArgumentException;
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
    }

    public function testConstructWithPrecisionAndScale(): void
    {
        $decimalType = new DecimalType(10, 3);

        $this->assertSame(10, $decimalType->getPrecision());
        $this->assertSame(3, $decimalType->getScale());
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

        $this->assertIsString($result);
        // The result should be the bytes representation of the decimal
        // scaled to the configured precision (2 decimal places)
        $this->assertSame($decimal->withScale(2)->toBytes(), $result);
    }

    public function testNormalizeWithDifferentScale(): void
    {
        $decimal = Decimal::fromDecimalRepresentation('123.4567'); // 4 decimal places
        
        $result = $this->decimalType->normalize($decimal);

        $this->assertIsString($result);
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

    public function testDenormalizeWithInvalidDatum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected bytes string for decimal denormalization');

        $this->decimalType->denormalize(123);
    }

    public function testDenormalizeWithNullDatum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected bytes string for decimal denormalization');

        $this->decimalType->denormalize(null);
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
}