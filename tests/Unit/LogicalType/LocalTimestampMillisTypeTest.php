<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\LocalTimestampMillisType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocalTimestampMillisTypeTest extends TestCase
{
    private LocalTimestampMillisType $timestampType;

    protected function setUp(): void
    {
        $this->timestampType = new LocalTimestampMillisType();
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
            ->with('Local timestamp value must be a DateTimeInterface object');

        $result = $this->timestampType->validate('2023-05-15', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('Local timestamp value must be a DateTimeInterface object');

        $result = $this->timestampType->validate(1684152645, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $dateTime = new DateTime('2023-05-15 12:30:45');

        $result = $this->timestampType->validate($dateTime, null);

        $this->assertTrue($result);
    }

    public function testNormalizeIgnoresTimezone(): void
    {
        $utc = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('UTC'));
        $pst = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('America/Los_Angeles'));

        $utcResult = $this->timestampType->normalize($utc);
        $pstResult = $this->timestampType->normalize($pst);

        // Should be the same since local timestamp ignores timezone
        $this->assertSame($utcResult, $pstResult);
    }

    public function testNormalizeWithMilliseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123');

        $result = $this->timestampType->normalize($dateTime);

        // The result should be milliseconds since epoch, ignoring timezone
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testNormalizeWithMicroseconds(): void
    {
        // Test that microseconds are truncated to milliseconds
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456');

        $result = $this->timestampType->normalize($dateTime);

        $this->assertIsInt($result);
        // Should only include first 3 digits of microseconds (123 milliseconds)
        $expectedMilliseconds = $result % 1000;
        $this->assertSame(123, $expectedMilliseconds);
    }

    public function testNormalizeWithoutMilliseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45');

        $result = $this->timestampType->normalize($dateTime);

        $this->assertIsInt($result);
        $this->assertSame(0, $result % 1000); // Should be exact seconds in milliseconds
    }

    public function testNormalizeWithEpochTime(): void
    {
        $dateTime = new DateTimeImmutable('1970-01-01 00:00:00.000');

        $result = $this->timestampType->normalize($dateTime);

        // Should be close to 0, accounting for local timezone offset
        $this->assertIsInt($result);
    }

    public function testDenormalizeWithZero(): void
    {
        $result = $this->timestampType->denormalize(0);

        $this->assertIsString($result);
        $this->assertSame('1970-01-01 00:00:00', $result);
    }

    public function testDenormalizeWithMilliseconds(): void
    {
        $milliseconds = 1684152645123; // Some timestamp with milliseconds

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('.123', $result);
        $this->assertStringNotContainsString('Z', $result); // No timezone indicator for local timestamp
        $this->assertStringNotContainsString('T', $result); // Local format uses space
    }

    public function testDenormalizeWithoutMilliseconds(): void
    {
        $milliseconds = 1684152645000; // Exact seconds

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('.', $result); // No milliseconds shown when zero
        $this->assertStringNotContainsString('Z', $result);
        $this->assertStringNotContainsString('T', $result);
    }

    public function testDenormalizeWithPartialMilliseconds(): void
    {
        $milliseconds = 1684152645001; // 1 millisecond

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('.001', $result);
    }

    public function testDenormalizeWithInvalidDatum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds) for local timestamp denormalization');

        $this->timestampType->denormalize('not an integer');
    }

    public function testDenormalizeWithNull(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds) for local timestamp denormalization');

        $this->timestampType->denormalize(null);
    }

    public function testDenormalizeWithFloat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected integer (milliseconds) for local timestamp denormalization');

        $this->timestampType->denormalize(1684152645.123);
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123');
        
        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid local timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('12:30:45.123', $denormalized);
        $this->assertStringNotContainsString('Z', $denormalized);
    }

    public function testNormalizeAndDenormalizeRoundTripWithoutMilliseconds(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45');
        
        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a valid local timestamp string
        $this->assertIsString($denormalized);
        $this->assertStringContainsString('12:30:45', $denormalized);
        $this->assertStringNotContainsString('.', $denormalized); // No decimal when no milliseconds
        $this->assertStringNotContainsString('Z', $denormalized);
    }

    public function testNormalizeWithNegativeTimestamp(): void
    {
        $dateTime = new DateTimeImmutable('1969-12-31 23:59:59.500');

        $result = $this->timestampType->normalize($dateTime);

        $this->assertIsInt($result);
    }

    public function testDenormalizeWithNegativeTimestamp(): void
    {
        $milliseconds = -1000; // 1 second before epoch

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertIsString($result);
        $this->assertStringContainsString('1969-12-31 23:59:59', $result);
    }

    public function testTimezoneIgnoredInNormalization(): void
    {
        $tokyo = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('Asia/Tokyo'));
        $london = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('Europe/London'));
        $newYork = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('America/New_York'));

        $tokyoResult = $this->timestampType->normalize($tokyo);
        $londonResult = $this->timestampType->normalize($london);
        $newYorkResult = $this->timestampType->normalize($newYork);

        // All should be the same since timezone is ignored
        $this->assertSame($tokyoResult, $londonResult);
        $this->assertSame($londonResult, $newYorkResult);
    }

    public function testDenormalizeFormatsWithoutTimezoneIndicator(): void
    {
        $milliseconds = 1684152645123;

        $result = $this->timestampType->denormalize($milliseconds);

        // Should not contain any timezone indicators
        $this->assertStringNotContainsString('Z', $result);
        $this->assertStringNotContainsString('+', $result);
        $this->assertStringNotContainsString('UTC', $result);
        $this->assertStringNotContainsString('T', $result); // Uses space separator for local format
        $this->assertStringContainsString(' ', $result); // Should have space between date and time
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
            $dateTime = new DateTimeImmutable("2023-05-15 12:30:45.{$microseconds}");
            
            $result = $this->timestampType->normalize($dateTime);
            $actualMilliseconds = $result % 1000;

            $this->assertSame($expectedMilliseconds, $actualMilliseconds, 
                "Failed for microseconds {$microseconds}, expected {$expectedMilliseconds} ms, got {$actualMilliseconds} ms");
        }
    }
}