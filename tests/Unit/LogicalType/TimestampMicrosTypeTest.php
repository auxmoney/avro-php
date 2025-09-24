<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\TimestampMicrosType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function microsecondsProvider(): Generator
    {
        yield '12 microseconds after epoch' => [new DateTime('1970-01-01 00:00:00.000012+00'), 12];
        yield '1001 microseconds after epoch' => [new DateTime('1970-01-01 00:00:00.001001+00'), 1001];
        yield 'epoch' => [new DateTime('1970-01-01 00:00:00.000000+00'), 0];
        yield '877 microseconds before epoch' => [new DateTime('1969-12-31 23:59:59.999123+00'), -877];
        yield '1001 microseconds before epoch' => [new DateTime('1969-12-31 23:59:59.998999+00'), -1001];
        yield 'date within summer time' => [new DateTime('2024-04-01T14:05:00.123456+00:00'), 1711980300123456];
        yield 'date out of summer time' => [new DateTime('2024-03-30T14:05:00.123456+00:00'), 1711807500123456];
        yield 'large future date' => [new DateTime('2100-01-01 00:00:00.000000+00'), 4102444800000000];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('microsecondsProvider')]
    public function testNormalize(object $dateTime, int $expected): void
    {
        $actual = $this->timestampType->normalize($dateTime);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('microsecondsProvider')]
    public function testDenormalize(DateTimeInterface $expected, int $input): void
    {
        $actual = $this->timestampType->denormalize($input);

        self::assertInstanceOf(DateTimeInterface::class, $actual);
        self::assertSame((new DateTime())->getTimezone()->getName(), $actual->getTimezone()->getName());
        self::assertEquals($expected, $actual);
    }

    public function testNormalizeWithoutMicroseconds(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15 12:30:45', new DateTimeZone('UTC'));

        $result = $this->timestampType->normalize($dateTime);

        $expected = $dateTime->getTimestamp() * 1000000;
        $this->assertSame($expected, $result);
    }

    public function testDenormalizeWithoutMicroseconds(): void
    {
        $microseconds = 1684152645000000; // Exact seconds

        $result = $this->timestampType->denormalize($microseconds);

        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertStringEndsWith('.000000', $result->format('Y-m-d\TH:i:s.u'));
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDateTime = new DateTimeImmutable('2023-05-15 12:30:45.123456', new DateTimeZone('UTC'));

        $normalized = $this->timestampType->normalize($originalDateTime);
        $denormalized = $this->timestampType->denormalize($normalized);

        // Check that we get back a DateTimeImmutable object
        $this->assertInstanceOf(DateTimeImmutable::class, $denormalized);
        $this->assertSame('2023-05-15T12:30:45.123456', $denormalized->format('Y-m-d\TH:i:s.u'));
    }
}
