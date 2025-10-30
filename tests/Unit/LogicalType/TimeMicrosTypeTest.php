<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimeMicrosType;
use Auxmoney\Avro\ValueObject\TimeOfDay;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TimeMicrosTypeTest extends TestCase
{
    private TimeMicrosType $timeMicrosType;

    protected function setUp(): void
    {
        $this->timeMicrosType = new TimeMicrosType();
    }

    public function testValidateWithValidTimeOfDay(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45, 123, 456);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMicrosType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(0, 0, 0, 0, 0);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMicrosType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithAlmostMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(23, 59, 59, 999, 999);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMicrosType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMicrosType->validate('12:30:45', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMicrosType->validate(45045123456, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithDateTime(): void
    {
        $dateTime = new DateTime('12:30:45');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMicrosType->validate($dateTime, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45);

        $result = $this->timeMicrosType->validate($timeOfDay, null);

        $this->assertTrue($result);
    }

    public function testNormalizeWithMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(0, 0, 0, 0, 0);

        $result = $this->timeMicrosType->normalize($timeOfDay);

        $this->assertSame(0, $result);
    }

    public function testNormalizeWithSpecificTime(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45, 123, 456);

        $result = $this->timeMicrosType->normalize($timeOfDay);

        // Calculate expected microseconds: (12*3600 + 30*60 + 45) * 1000000 + 123*1000 + 456
        $expected = (12 * 3600 + 30 * 60 + 45) * 1000000 + 123 * 1000 + 456;
        $this->assertSame($expected, $result);
        $this->assertSame($timeOfDay->totalMicroseconds, $result);
    }

    public function testNormalizeWithAlmostMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(23, 59, 59, 999, 999);

        $result = $this->timeMicrosType->normalize($timeOfDay);

        $expected = (23 * 3600 + 59 * 60 + 59) * 1000000 + 999 * 1000 + 999;
        $this->assertSame($expected, $result);
        $this->assertSame($timeOfDay->totalMicroseconds, $result);
    }

    public function testNormalizeWithNoon(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 0, 0, 0, 0);

        $result = $this->timeMicrosType->normalize($timeOfDay);

        $expected = 12 * 3600 * 1000000; // 12:00:00.000000
        $this->assertSame($expected, $result);
    }

    public function testDenormalizeWithZero(): void
    {
        $result = $this->timeMicrosType->denormalize(0);

        $this->assertInstanceOf(TimeOfDay::class, $result);
        $this->assertSame(0, $result->totalMicroseconds);
        $this->assertSame(0, $result->getHours());
        $this->assertSame(0, $result->getMinutes());
        $this->assertSame(0, $result->getSeconds());
        $this->assertSame(0, $result->getMilliseconds());
        $this->assertSame(0, $result->getMicroseconds());
    }

    public function testDenormalizeWithSpecificTime(): void
    {
        $microseconds = (12 * 3600 + 30 * 60 + 45) * 1000000 + 123 * 1000 + 456;

        $result = $this->timeMicrosType->denormalize($microseconds);

        $this->assertInstanceOf(TimeOfDay::class, $result);
        $this->assertSame($microseconds, $result->totalMicroseconds);
        $this->assertSame(12, $result->getHours());
        $this->assertSame(30, $result->getMinutes());
        $this->assertSame(45, $result->getSeconds());
        $this->assertSame(123, $result->getMilliseconds());
        $this->assertSame(123456, $result->getMicroseconds());
    }

    public function testDenormalizeWithAlmostMidnight(): void
    {
        $microseconds = (23 * 3600 + 59 * 60 + 59) * 1000000 + 999 * 1000 + 999;

        $result = $this->timeMicrosType->denormalize($microseconds);

        $this->assertInstanceOf(TimeOfDay::class, $result);
        $this->assertSame(23, $result->getHours());
        $this->assertSame(59, $result->getMinutes());
        $this->assertSame(59, $result->getSeconds());
        $this->assertSame(999, $result->getMilliseconds());
        $this->assertSame(999999, $result->getMicroseconds());
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalTime = TimeOfDay::fromComponents(14, 25, 30, 789, 123);

        $normalized = $this->timeMicrosType->normalize($originalTime);
        $denormalized = $this->timeMicrosType->denormalize($normalized);

        $this->assertInstanceOf(TimeOfDay::class, $denormalized);
        $this->assertSame($originalTime->totalMicroseconds, $denormalized->totalMicroseconds);
        $this->assertSame($originalTime->getHours(), $denormalized->getHours());
        $this->assertSame($originalTime->getMinutes(), $denormalized->getMinutes());
        $this->assertSame($originalTime->getSeconds(), $denormalized->getSeconds());
        $this->assertSame($originalTime->getMilliseconds(), $denormalized->getMilliseconds());
        $this->assertSame($originalTime->getMicroseconds(), $denormalized->getMicroseconds());
    }

    public function testNormalizeAndDenormalizeRoundTripWithMidnight(): void
    {
        $originalTime = TimeOfDay::fromComponents(0, 0, 0, 0, 0);

        $normalized = $this->timeMicrosType->normalize($originalTime);
        $denormalized = $this->timeMicrosType->denormalize($normalized);

        $this->assertSame($originalTime->totalMicroseconds, $denormalized->totalMicroseconds);
        $this->assertSame(0, $normalized);
    }

    public function testNormalizeAndDenormalizeRoundTripWithAlmostMidnight(): void
    {
        $originalTime = TimeOfDay::fromComponents(23, 59, 59, 999, 999);

        $normalized = $this->timeMicrosType->normalize($originalTime);
        $denormalized = $this->timeMicrosType->denormalize($normalized);

        $this->assertSame($originalTime->totalMicroseconds, $denormalized->totalMicroseconds);
        $this->assertSame(86399999999, $normalized); // Should be max value for a day
    }

    public function testNormalizeWithTimeFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 14:30:45.123456');
        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $result = $this->timeMicrosType->normalize($timeOfDay);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);

        // Verify the time components are preserved
        $denormalized = $this->timeMicrosType->denormalize($result);
        $this->assertSame(14, $denormalized->getHours());
        $this->assertSame(30, $denormalized->getMinutes());
        $this->assertSame(45, $denormalized->getSeconds());
        $this->assertSame(123, $denormalized->getMilliseconds());
        $this->assertSame(123456, $denormalized->getMicroseconds());
    }

    public function testValidateAndNormalizeChain(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(9, 15, 30, 500, 750);
        $context = $this->createMock(ValidationContextInterface::class);

        $isValid = $this->timeMicrosType->validate($timeOfDay, $context);
        $this->assertTrue($isValid);

        $normalized = $this->timeMicrosType->normalize($timeOfDay);
        $this->assertIsInt($normalized);
        $this->assertSame($timeOfDay->totalMicroseconds, $normalized);
    }
}
