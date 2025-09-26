<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\DateType;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class DateTypeTest extends TestCase
{
    private DateType $dateType;

    protected function setUp(): void
    {
        $this->dateType = new DateType();
    }

    public function testValidateWithValidDateTime(): void
    {
        $dateTime = new DateTime('2023-05-15');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->dateType->validate($dateTime, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithValidDateTimeImmutable(): void
    {
        $dateTime = new DateTimeImmutable('2023-05-15');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->dateType->validate($dateTime, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('expected DateTimeInterface, got string');

        $result = $this->dateType->validate('not a date', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithNullDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('expected DateTimeInterface, got NULL');

        $result = $this->dateType->validate(null, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $dateTime = new DateTime('2023-05-15');

        $result = $this->dateType->validate($dateTime, null);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatumAndNullContext(): void
    {
        $result = $this->dateType->validate('not a date', null);

        $this->assertFalse($result);
    }

    public function testNormalizeEpochDate(): void
    {
        $epochDate = new DateTimeImmutable('1970-01-01');

        $result = $this->dateType->normalize($epochDate);

        $this->assertSame(0, $result);
    }

    public function testNormalizeDateAfterEpoch(): void
    {
        $date = new DateTimeImmutable('1970-01-02');

        $result = $this->dateType->normalize($date);

        $this->assertSame(1, $result);
    }

    public function testNormalizeDateBeforeEpoch(): void
    {
        $date = new DateTimeImmutable('1969-12-31');

        $result = $this->dateType->normalize($date);

        $this->assertSame(-1, $result);
    }

    public function testNormalizeDateFarFromEpoch(): void
    {
        $date = new DateTimeImmutable('2000-01-01');

        $result = $this->dateType->normalize($date);

        // 2000-01-01 is 10957 days after 1970-01-01
        $this->assertSame(10957, $result);
    }

    public function testNormalizeWithTimezone(): void
    {
        $utc = new DateTimeZone('UTC');
        $pacific = new DateTimeZone('America/Los_Angeles');
        
        $utcDate = new DateTimeImmutable('2023-05-15 00:00:00', $utc);
        $pacificDate = new DateTimeImmutable('2023-05-15 00:00:00', $pacific);

        $utcResult = $this->dateType->normalize($utcDate);
        $pacificResult = $this->dateType->normalize($pacificDate);

        $this->assertSame($utcResult, $pacificResult);
    }

    public function testDenormalizeEpochDate(): void
    {
        $result = $this->dateType->denormalize(0);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('1970-01-01', $result->format('Y-m-d'));
    }

    public function testDenormalizeDateAfterEpoch(): void
    {
        $result = $this->dateType->denormalize(1);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('1970-01-02', $result->format('Y-m-d'));
    }

    public function testDenormalizeDateBeforeEpoch(): void
    {
        $result = $this->dateType->denormalize(-1);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('1969-12-31', $result->format('Y-m-d'));
    }

    public function testDenormalizeDateFarFromEpoch(): void
    {
        $result = $this->dateType->denormalize(10957);

        $this->assertInstanceOf(DateTimeInterface::class, $result);
        $this->assertSame('2000-01-01', $result->format('Y-m-d'));
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalDate = new DateTimeImmutable('2023-05-15');
        
        $normalized = $this->dateType->normalize($originalDate);
        $denormalized = $this->dateType->denormalize($normalized);

        $this->assertSame($originalDate->format('Y-m-d'), $denormalized->format('Y-m-d'));
    }

    public function testNormalizeAndDenormalizeRoundTripWithBeforeEpoch(): void
    {
        $originalDate = new DateTimeImmutable('1950-03-15');
        
        $normalized = $this->dateType->normalize($originalDate);
        $denormalized = $this->dateType->denormalize($normalized);

        $this->assertSame($originalDate->format('Y-m-d'), $denormalized->format('Y-m-d'));
    }
}