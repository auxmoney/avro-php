<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\DurationType;
use Auxmoney\Avro\ValueObject\Duration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class DurationTypeTest extends TestCase
{
    private DurationType $durationType;

    protected function setUp(): void
    {
        $this->durationType = new DurationType();
    }

    public function testValidateWithValidDuration(): void
    {
        $duration = new Duration(12, 30, 45000); // 12 months, 30 days, 45000 milliseconds
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->durationType->validate($duration, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithZeroValues(): void
    {
        $duration = new Duration(0, 0, 0);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->durationType->validate($duration, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithDurationValueObject(): void
    {
        $duration = new Duration(5, 10, 2500);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->durationType->validate($duration, $context);

        $this->assertTrue($result);
    }



    public function testValidateWithInvalidType(): void
    {
        $duration = 123;
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Duration value must be a Duration object');

        $result = $this->durationType->validate($duration, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidObject(): void
    {
        $duration = new stdClass();
        
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Duration value must be a Duration object');

        $result = $this->durationType->validate($duration, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $duration = new Duration(12, 30, 45000);

        $result = $this->durationType->validate($duration, null);

        $this->assertTrue($result);
    }

    public function testNormalizeWithDuration(): void
    {
        $duration = new Duration(12, 30, 45000);

        $result = $this->durationType->normalize($duration);

        $this->assertIsString($result);
        $this->assertSame(12, strlen($result));
        
        // Verify the packed values
        $unpacked = unpack('V3', $result);
        $this->assertSame([1 => 12, 2 => 30, 3 => 45000], $unpacked);
    }

    public function testNormalizeWithDurationValueObject(): void
    {
        $duration = new Duration(5, 15, 2500);

        $result = $this->durationType->normalize($duration);

        $this->assertIsString($result);
        $this->assertSame(12, strlen($result));
        
        // Verify the packed values
        $unpacked = unpack('V3', $result);
        $this->assertSame([1 => 5, 2 => 15, 3 => 2500], $unpacked);
    }

    public function testNormalizeWithZeroValues(): void
    {
        $duration = new Duration(0, 0, 0);

        $result = $this->durationType->normalize($duration);

        $this->assertIsString($result);
        $this->assertSame(12, strlen($result));
        
        // Verify all zeros
        $unpacked = unpack('V3', $result);
        $this->assertSame([1 => 0, 2 => 0, 3 => 0], $unpacked);
    }

    public function testDenormalizeWithValidBytes(): void
    {
        $bytes = pack('VVV', 12, 30, 45000);

        $result = $this->durationType->denormalize($bytes);

        $this->assertInstanceOf(Duration::class, $result);
        $this->assertSame(12, $result->months);
        $this->assertSame(30, $result->days);
        $this->assertSame(45000, $result->milliseconds);
    }

    public function testDenormalizeWithZeroValues(): void
    {
        $bytes = pack('VVV', 0, 0, 0);

        $result = $this->durationType->denormalize($bytes);

        $this->assertInstanceOf(Duration::class, $result);
        $this->assertSame(0, $result->months);
        $this->assertSame(0, $result->days);
        $this->assertSame(0, $result->milliseconds);
    }

    public function testDenormalizeWithInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected 12-byte string for duration denormalization');

        $this->durationType->denormalize(str_repeat("\x00", 11));
    }

    public function testDenormalizeWithNonString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected 12-byte string for duration denormalization');

        $this->durationType->denormalize([12, 30, 45000]);
    }



    public function testNormalizeAndDenormalizeRoundTripWithDurationValueObject(): void
    {
        $original = new Duration(10, 25, 5500);
        
        $normalized = $this->durationType->normalize($original);
        $denormalized = $this->durationType->denormalize($normalized);

        $this->assertInstanceOf(Duration::class, $denormalized);
        $this->assertSame(10, $denormalized->months);
        $this->assertSame(25, $denormalized->days);
        $this->assertSame(5500, $denormalized->milliseconds);
    }


}