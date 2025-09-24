<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimestampMillisType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function millisecondsProvider(): Generator
    {
        yield '12 milliseconds after epoch' => [new DateTime('1970-01-01 00:00:00.012+00'), 12];
        yield '1001 milliseconds after epoch' => [new DateTime('1970-01-01 00:00:01.001+00'), 1001];
        yield 'epoch' => [new DateTime('1970-01-01 00:00:00.000+00'), 0];
        yield '877 milliseconds before epoch' => [new DateTime('1969-12-31 23:59:59.123+00'), -877];
        yield '1001 milliseconds before epoch' => [new DateTime('1969-12-31 23:59:58.999+00'), -1001];
        yield 'date within summer time' => [new DateTime('2024-04-01T14:05:00.123+00:00'), 1711980300123];
        yield 'date out of summer time' => [new DateTime('2024-03-30T14:05:00.123+00:00'), 1711807500123];
        yield 'large future date' => [new DateTime('2100-01-01 00:00:00.000+00'), 4102444800000];
    }

    #[DataProvider('millisecondsProvider')]
    public function testNormalize(object $dateTime, int $expected): void
    {
        $actual = $this->timestampType->normalize($dateTime);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('millisecondsProvider')]
    public function testDenormalize(DateTimeInterface $expected, int $input): void
    {
        $actual = $this->timestampType->denormalize($input);

        self::assertInstanceOf(DateTimeInterface::class, $actual);
        self::assertSame((new DateTime())->getTimezone()->getName(), $actual->getTimezone()->getName());
        self::assertEquals($expected, $actual);
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

    public function testDenormalizeWithoutMilliseconds(): void
    {
        $milliseconds = 1684152645000; // Exact seconds

        $result = $this->timestampType->denormalize($milliseconds);

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertStringEndsWith('.000', $result->format('Y-m-d\TH:i:s.v'));
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123', new DateTimeZone('UTC'));

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a DateTimeImmutable object
        $this->assertInstanceOf(DateTimeImmutable::class, $denormalized);
        $this->assertSame('2023-05-15T12:30:45.123', $denormalized->format('Y-m-d\TH:i:s.v'));
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
            $this->assertIsInt($result);
            $actualMilliseconds = $result % 1000;

            $this->assertSame(
                $expectedMilliseconds,
                $actualMilliseconds,
                "Failed for microseconds {$microseconds}, expected {$expectedMilliseconds} ms, got {$actualMilliseconds} ms",
            );
        }
    }
}
