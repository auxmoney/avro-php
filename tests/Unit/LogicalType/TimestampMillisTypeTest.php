<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimestampMillisType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TimestampMillisTypeTest extends TestCase
{
    private TimestampMillisType $timestampType;

    protected function setUp(): void
    {
        $this->timestampType = new TimestampMillisType();
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
        $dateTime = new DateTimeImmutable('1970-01-01 00:00:00.000', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $this->assertSame(0, $result);
    }

    public function testNormalizeWithMilliseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000 + 123;
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithMicroseconds(): void
    {
        // Test that microseconds are truncated to milliseconds
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000 + 123; // Only first 3 digits
        $this->assertSame($expected, $result);
    }

    public function testNormalizeWithoutMilliseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000;
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
        $this->assertSame('1970-01-01T00:00:00.000Z', $result);
    }

    public function testDenormalizeWithMilliseconds(): void
    {
        $milliseconds = 1684152645123; // Some timestamp with milliseconds

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('T', $result);
        $this->assertStringEndsWith('Z', $result);
        $this->assertStringContainsString('.123', $result);
    }

    public function testDenormalizeWithoutMilliseconds(): void
    {
        $milliseconds = 1684152645000; // Exact seconds

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringEndsWith('.000Z', $result);
    }

    public function testDenormalizeWithInvalidDatum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds since Unix epoch) for timestamp denormalization');

        $this->timestampType->denormalize('not an integer');
    }

    public function testDenormalizeWithNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds since Unix epoch) for timestamp denormalization');

        $this->timestampType->denormalize(null);
    }

    public function testDenormalizeWithFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds since Unix epoch) for timestamp denormalization');

        $this->timestampType->denormalize(1684152645.123);
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('UTC'));
        
        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('2023-05-15T12:30:45.123Z', $denormalized);
    }

    public function testNormalizeAndDenormalizeRoundTripWithoutMilliseconds(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));
        
        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('2023-05-15T12:30:45.000Z', $denormalized);
    }

    public function testNormalizeWithNegativeTimestamp(): void
    {
        $dateTime = new DateTimeImmutable('1969-12-31 23:59:59.500', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000 + 500;
        $this->assertSame($expected, $result);
        $this->assertLessThan(0, $result);
    }

    public function testDenormalizeWithNegativeTimestamp(): void
    {
        $milliseconds = -1000; // 1 second before epoch

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('1969-12-31T23:59:59.000Z', $result);
    }

    public function testDenormalizeWithLargePositiveTimestamp(): void
    {
        $milliseconds = 4102444800000; // 2100-01-01 00:00:00

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('2100-01-01T00:00:00.000Z', $result);
    }

    public function testNormalizeWithMicrosecondsRounding(): void
    {
        // Test various microsecond values to ensure proper millisecond conversion
        $testCases = [
            '123456' => 123, // Truncated
            '567890' => 567, // Truncated
            '999999' => 999, // Truncated
            '000123' => 0,   // Truncated
        ];

        foreach ($testCases as $microseconds => $expectedMilliseconds) {
            $dateTime = new DateTimeImmutable("2023-05-15 12:30:45.{$microseconds}", new DateTimeZone('UTC'));
            
            $result = $this->timestampType->normalize($dateTime);
            $actualMilliseconds = $result % 1000;

            $this->assertSame($expectedMilliseconds, $actualMilliseconds, 
                "Failed for microseconds {$microseconds}, expected {$expectedMilliseconds} ms, got {$actualMilliseconds} ms");
        }
    }
}