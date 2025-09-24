<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimestampMicrosType;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class TimestampMicrosTypeTest extends TestCase
{
    private TimestampMicrosType $timestampType;

    protected function setUp(): void
    {
        $this->timestampType = new TimestampMicrosType();
    }

    public function testValidateWithValidDateTime(): void
    {
        $dateTime = new DateTime('2023-05-15 12:30:45');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timestampType->validate($dateTime, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithValidDateTimeImmutable(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->timestampType->validate($dateTime, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Timestamp value must be a DateTimeInterface object');

        $result = $this->timestampType->validate('2023-05-15', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Timestamp value must be a DateTimeInterface object');

        $result = $this->timestampType->validate(1684152645, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $dateTime = new DateTime('2023-05-15 12:30:45');

        $result = $this->timestampType->validate($dateTime, null);

        $this->assertTrue($result);
    }

    public function testNormalizeWithEpochTime(): void
    {
        $dateTime = new DateTimeImmutable('1970-01-01 00:00:00.000000', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $this->assertSame(0, $result);
    }

    public function testNormalizeWithMicroseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000000 + 123456;
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithoutMicroseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000000;
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithTimezone(): void
    {
        $utc = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));
        $pst = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('America/Los_Angeles'));

        $utcResult = $this->timestampType->normalize($utc);
        $pstResult = $this->timestampType->normalize($pst);

        // PST is 7-8 hours behind UTC, so the timestamp should be different
        $this->assertNotEquals($utcResult, $pstResult);
    }

    public function testDenormalizeWithZero(): void
    {
        $result = $this->timestampType->denormalize(0);

        $this->assertIsString($result);
        $this->assertSame('1970-01-01T00:00:00.000000Z', $result);
    }

    public function testDenormalizeWithMicroseconds(): void
    {
        $microseconds = 1684152645123456; // Some timestamp with microseconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('T', $result);
        $this->assertStringEndsWith('Z', $result);
        $this->assertStringContainsString('.123456', $result);
    }

    public function testDenormalizeWithoutMicroseconds(): void
    {
        $microseconds = 1684152645000000; // Exact seconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertIsString($result);
        $this->assertStringEndsWith('.000000Z', $result);
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('UTC'));

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('2023-05-15T12:30:45.123456Z', $denormalized);
    }

    public function testNormalizeAndDenormalizeRoundTripWithoutMicroseconds(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('2023-05-15T12:30:45.000000Z', $denormalized);
    }

    public function testNormalizeWithNegativeTimestamp(): void
    {
        $dateTime = new DateTimeImmutable('1969-12-31 23:59:59', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000000 + (int) $dateTime->format('u');
        $this->assertSame($expected, $result);
        $this->assertLessThan(0, $result);
    }

    public function testDenormalizeWithNegativeTimestamp(): void
    {
        $microseconds = -1000000; // 1 second before epoch

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('1969-12-31T23:59:59.000000Z', $result);
    }
}
