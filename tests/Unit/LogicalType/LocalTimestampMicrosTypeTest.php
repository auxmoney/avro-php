<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\LocalTimestampMicrosType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LocalTimestampMicrosTypeTest extends TestCase
{
    private LocalTimestampMicrosType $timestampType;

    protected function setUp(): void
    {
        $this->timestampType = new LocalTimestampMicrosType();
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
            ->with('expected DateTimeInterface, got string');

        $result = $this->timestampType->validate('2023-05-15', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('expected DateTimeInterface, got integer');

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
        $utc = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('UTC'));
        $pst = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('America/Los_Angeles'));

        $utcResult = $this->timestampType->normalize($utc);
        $pstResult = $this->timestampType->normalize($pst);

        // Should be the same since local timestamp ignores timezone
        $this->assertSame($utcResult, $pstResult);
    }

    public function testNormalizeWithMicroseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456');

        $result = $this->timestampType->normalize($dateTime);

        // The result should be microseconds since epoch, ignoring timezone
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testNormalizeWithoutMicroseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45');

        $result = $this->timestampType->normalize($dateTime);

        $this->assertIsInt($result);
        $this->assertSame(0, $result % 1000000); // Should be exact seconds in microseconds
    }

    public function testNormalizeWithEpochTime(): void
    {
        $dateTime = new DateTimeImmutable('1970-01-01 00:00:00.000000');

        $result = $this->timestampType->normalize($dateTime);

        // Should be close to 0, accounting for local timezone offset
        $this->assertIsInt($result);
    }

    public function testDenormalizeWithZero(): void
    {
        $result = $this->timestampType->denormalize(0);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('1970-01-01T00:00:00', $result->format('Y-m-d\TH:i:s'));
    }

    public function testDenormalizeWithMicroseconds(): void
    {
        $microseconds = 1684152645123456; // Some timestamp with microseconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertStringContainsString('.123456', $result->format('Y-m-d H:i:s.u'));
    }

    public function testDenormalizeWithoutMicroseconds(): void
    {
        $microseconds = 1684152645000000; // Exact seconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertStringNotContainsString('.', $result->format('Y-m-d H:i:s')); // No microseconds shown when zero
    }

    public function testDenormalizeWithPartialMicroseconds(): void
    {
        $microseconds = 1684152645000100; // 100 microseconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertStringContainsString('.000100', $result->format('Y-m-d H:i:s.u'));
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456');

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a DateTimeInterface object
        $this->assertInstanceOf(DateTimeInterface::class, $denormalized);
        $this->assertStringContainsString('12:30:45.123456', $denormalized->format('Y-m-d H:i:s.u'));
    }

    public function testNormalizeAndDenormalizeRoundTripWithoutMicroseconds(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45');

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a DateTimeInterface object
        $this->assertInstanceOf(DateTimeInterface::class, $denormalized);
        $this->assertStringContainsString('12:30:45', $denormalized->format('Y-m-d H:i:s'));
        $this->assertStringNotContainsString('.', $denormalized->format('Y-m-d H:i:s')); // No decimal when no microseconds
    }

    public function testNormalizeWithNegativeTimestamp(): void
    {
        $dateTime = new DateTimeImmutable('1969-12-31 23:59:59.500000');

        $result = $this->timestampType->normalize($dateTime);

        $this->assertIsInt($result);
    }

    public function testDenormalizeWithNegativeTimestamp(): void
    {
        $microseconds = -1000000; // 1 second before epoch

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertStringContainsString('1969-12-31 23:59:59', $result->format('Y-m-d H:i:s'));
    }

    public function testTimezoneIgnoredInNormalization(): void
    {
        $tokyo = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('Asia/Tokyo'));
        $london = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('Europe/London'));
        $newYork = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('America/New_York'));

        $tokyoResult = $this->timestampType->normalize($tokyo);
        $londonResult = $this->timestampType->normalize($london);
        $newYorkResult = $this->timestampType->normalize($newYork);

        // All should be the same since timezone is ignored
        $this->assertSame($tokyoResult, $londonResult);
        $this->assertSame($londonResult, $newYorkResult);
    }

    public function testDenormalizeFormatsWithoutTimezoneIndicator(): void
    {
        $microseconds = 1684152645123456;

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        
        // The result should be a proper DateTimeInterface object
        // Format it to check it doesn't contain timezone indicators when formatted as local
        $formatted = $result->format('Y-m-d H:i:s.u');
        $this->assertStringContainsString('12:10:45.123456', $formatted);
    }

    public static function localTimestampMicrosecondsProvider(): Generator
    {
        // For local timestamp: time components are treated as UTC regardless of original timezone
        yield '12 microseconds after epoch (local as UTC)' => [new DateTime('1970-01-01 00:00:00.000012'), 12];
        yield '1001 microseconds after epoch (local as UTC)' => [new DateTime('1970-01-01 00:00:00.001001'), 1001];
        yield 'epoch (local as UTC)' => [new DateTime('1970-01-01 00:00:00.000000'), 0];
        yield '877 microseconds before epoch (local as UTC)' => [new DateTime('1969-12-31 23:59:59.999123'), -877];
        yield '1001 microseconds before epoch (local as UTC)' => [new DateTime('1969-12-31 23:59:59.998999'), -1001];
        // These should be treated as the time components in UTC, ignoring timezone
        yield 'date within summer time (local as UTC)' => [new DateTime('2024-04-01T14:05:00.123456+02:00'), 1711980300123456];
        yield 'date out of summer time (local as UTC)' => [new DateTime('2024-03-30T14:05:00.123456+01:00'), 1711807500123456];
        yield 'large future date (local as UTC)' => [new DateTime('2100-01-01 00:00:00.000000'), 4102444800000000];
    }

    #[DataProvider('localTimestampMicrosecondsProvider')]
    public function testNormalizeWithProvider(object $dateTime, int $expected): void
    {
        $actual = $this->timestampType->normalize($dateTime);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('localTimestampMicrosecondsProvider')]
    public function testDenormalizeWithProvider(DateTimeInterface $expected, int $input): void
    {
        $actual = $this->timestampType->denormalize($input);

        self::assertInstanceOf(DateTimeInterface::class, $actual);
        self::assertSame((new DateTime())->getTimezone()->getName(), $actual->getTimezone()->getName());
        // For local timestamp: the time components should match (ignoring timezone)
        self::assertSame($expected->format('Y-m-d H:i:s.u'), $actual->format('Y-m-d H:i:s.u'));
    }
}
