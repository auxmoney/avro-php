<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimeMillisType;
use Auxmoney\Avro\ValueObject\TimeOfDay;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TimeMillisTypeTest extends TestCase
{
    private TimeMillisType $timeMillisType;

    protected function setUp(): void
    {
        $this->timeMillisType = new TimeMillisType();
    }

    public function testValidateWithValidTimeOfDay(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45, 123);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMillisType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(0, 0, 0, 0, 0);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMillisType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithAlmostMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(23, 59, 59, 999);
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timeMillisType->validate($timeOfDay, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMillisType->validate('12:30:45', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMillisType->validate(45045123, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithDateTime(): void
    {
        $dateTime = new DateTime('12:30:45');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Time value must be a TimeOfDay object');

        $result = $this->timeMillisType->validate($dateTime, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45);

        $result = $this->timeMillisType->validate($timeOfDay, null);

        $this->assertTrue($result);
    }

    public function testNormalizeWithMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(0, 0, 0, 0, 0);

        $result = $this->timeMillisType->normalize($timeOfDay);

        $this->assertSame(0, $result);
    }

    public function testNormalizeWithSpecificTime(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45, 123);

        $result = $this->timeMillisType->normalize($timeOfDay);

        // Calculate expected milliseconds: (12*3600 + 30*60 + 45) * 1000 + 123
        $expected = (12 * 3600 + 30 * 60 + 45) * 1000 + 123;
        $this->assertSame($expected, $result);
        $this->assertSame($timeOfDay->getTotalMilliseconds(), $result);
    }

    public function testNormalizeWithMicroseconds(): void
    {
        // Microseconds should be truncated in millisecond normalization
        $timeOfDay = TimeOfDay::fromComponents(12, 30, 45, 123, 456);

        $result = $this->timeMillisType->normalize($timeOfDay);

        // Should only include milliseconds, not microseconds
        $expected = (12 * 3600 + 30 * 60 + 45) * 1000 + 123;
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithAlmostMidnight(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(23, 59, 59, 999);

        $result = $this->timeMillisType->normalize($timeOfDay);

        $expected = (23 * 3600 + 59 * 60 + 59) * 1000 + 999;
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithNoon(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(12, 0, 0, 0, 0);

        $result = $this->timeMillisType->normalize($timeOfDay);

        $expected = 12 * 3600 * 1000; // 12:00:00.000
        $this->assertSame($expected, $result);
    }

    public function testDenormalizeWithZero(): void
    {
        $result = $this->timeMillisType->denormalize(0);

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
        $milliseconds = (12 * 3600 + 30 * 60 + 45) * 1000 + 123;

        $result = $this->timeMillisType->denormalize($milliseconds);

        $this->assertInstanceOf(TimeOfDay::class, $result);
        $this->assertSame(12, $result->getHours());
        $this->assertSame(30, $result->getMinutes());
        $this->assertSame(45, $result->getSeconds());
        $this->assertSame(123, $result->getMilliseconds());
        $this->assertSame(123000, $result->getMicroseconds()); // 123 milliseconds = 123000 microseconds
    }

    public function testDenormalizeWithAlmostMidnight(): void
    {
        $milliseconds = (23 * 3600 + 59 * 60 + 59) * 1000 + 999;

        $result = $this->timeMillisType->denormalize($milliseconds);

        $this->assertInstanceOf(TimeOfDay::class, $result);
        $this->assertSame(23, $result->getHours());
        $this->assertSame(59, $result->getMinutes());
        $this->assertSame(59, $result->getSeconds());
        $this->assertSame(999, $result->getMilliseconds());
        $this->assertSame(999000, $result->getMicroseconds());
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalTime = TimeOfDay::fromComponents(14, 25, 30, 789);

        $normalized = $this->timeMillisType->normalize($originalTime);
        $denormalized = $this->timeMillisType->denormalize($normalized);

        $this->assertInstanceOf(TimeOfDay::class, $denormalized);
        $this->assertSame($originalTime->getHours(), $denormalized->getHours());
        $this->assertSame($originalTime->getMinutes(), $denormalized->getMinutes());
        $this->assertSame($originalTime->getSeconds(), $denormalized->getSeconds());
        $this->assertSame($originalTime->getMilliseconds(), $denormalized->getMilliseconds());
        $this->assertSame($originalTime->getMilliseconds() * 1000, $denormalized->getMicroseconds()); // Only millisecond precision
    }

    public function testNormalizeAndDenormalizeRoundTripWithMicroseconds(): void
    {
        // Test that microseconds are lost in round trip
        $originalTime = TimeOfDay::fromComponents(14, 25, 30, 789, 456);

        $normalized = $this->timeMillisType->normalize($originalTime);
        $denormalized = $this->timeMillisType->denormalize($normalized);

        $this->assertInstanceOf(TimeOfDay::class, $denormalized);
        $this->assertSame($originalTime->getHours(), $denormalized->getHours());
        $this->assertSame($originalTime->getMinutes(), $denormalized->getMinutes());
        $this->assertSame($originalTime->getSeconds(), $denormalized->getSeconds());
        $this->assertSame($originalTime->getMilliseconds(), $denormalized->getMilliseconds());
        $this->assertSame($originalTime->getMilliseconds() * 1000, $denormalized->getMicroseconds()); // Microseconds are truncated
    }

    public function testNormalizeAndDenormalizeRoundTripWithMidnight(): void
    {
        $originalTime = TimeOfDay::fromComponents(0, 0, 0, 0, 0);

        $normalized = $this->timeMillisType->normalize($originalTime);
        $denormalized = $this->timeMillisType->denormalize($normalized);

        $this->assertSame($originalTime->getHours(), $denormalized->getHours());
        $this->assertSame($originalTime->getMinutes(), $denormalized->getMinutes());
        $this->assertSame($originalTime->getSeconds(), $denormalized->getSeconds());
        $this->assertSame($originalTime->getMilliseconds(), $denormalized->getMilliseconds());
        $this->assertSame(0, $normalized);
    }

    public function testNormalizeAndDenormalizeRoundTripWithAlmostMidnight(): void
    {
        $originalTime = TimeOfDay::fromComponents(23, 59, 59, 999);

        $normalized = $this->timeMillisType->normalize($originalTime);
        $denormalized = $this->timeMillisType->denormalize($normalized);

        $this->assertSame($originalTime->getHours(), $denormalized->getHours());
        $this->assertSame($originalTime->getMinutes(), $denormalized->getMinutes());
        $this->assertSame($originalTime->getSeconds(), $denormalized->getSeconds());
        $this->assertSame($originalTime->getMilliseconds(), $denormalized->getMilliseconds());
        $this->assertSame(86399999, $normalized); // Should be max value for a day in milliseconds
    }

    public function testNormalizeWithTimeFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 14:30:45.123456');
        $timeOfDay = TimeOfDay::fromDateTime($dateTime);

        $result = $this->timeMillisType->normalize($timeOfDay);

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);

        // Verify the time components are preserved (except microseconds)
        $denormalized = $this->timeMillisType->denormalize($result);
        $this->assertSame(14, $denormalized->getHours());
        $this->assertSame(30, $denormalized->getMinutes());
        $this->assertSame(45, $denormalized->getSeconds());
        $this->assertSame(123, $denormalized->getMilliseconds());
        $this->assertSame(123000, $denormalized->getMicroseconds()); // Microseconds are truncated
    }

    public function testValidateAndNormalizeChain(): void
    {
        $timeOfDay = TimeOfDay::fromComponents(9, 15, 30, 500);
        $context = $this->createMock(ValidationContextInterface::class);

        $isValid = $this->timeMillisType->validate($timeOfDay, $context);
        $this->assertTrue($isValid);

        $normalized = $this->timeMillisType->normalize($timeOfDay);
        $this->assertIsInt($normalized);
        $this->assertSame($timeOfDay->getTotalMilliseconds(), $normalized);
    }

    public function testDenormalizeConvertsMillisecondsToMicroseconds(): void
    {
        $milliseconds = 123456; // Arbitrary milliseconds since midnight

        $result = $this->timeMillisType->denormalize($milliseconds);

        // The constructor should receive microseconds (milliseconds * 1000)
        $expectedMicroseconds = $milliseconds * 1000;
        $this->assertSame($expectedMicroseconds, $result->totalMicroseconds);
    }

    public function testMicrosecondPrecisionLoss(): void
    {
        // Test various microsecond values to ensure they're truncated
        $testCases = [
            ['input' => TimeOfDay::fromComponents(12, 30, 45, 123, 456), 'expectedMs' => 123],
            ['input' => TimeOfDay::fromComponents(12, 30, 45, 789, 999), 'expectedMs' => 789],
            ['input' => TimeOfDay::fromComponents(12, 30, 45, 0, 500), 'expectedMs' => 0],
        ];

        foreach ($testCases as $testCase) {
            $normalized = $this->timeMillisType->normalize($testCase['input']);
            $denormalized = $this->timeMillisType->denormalize($normalized);

            $this->assertSame($testCase['expectedMs'], $denormalized->getMilliseconds());
            $this->assertSame(
                $testCase['expectedMs'] * 1000,
                $denormalized->getMicroseconds(),
            ); // Always milliseconds * 1000 after round trip
        }
    }
}
