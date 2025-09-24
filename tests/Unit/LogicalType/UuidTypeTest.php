<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\LogicalType\UuidType;
use Auxmoney\Avro\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class UuidTypeTest extends TestCase
{
    private UuidType $uuidType;

    protected function setUp(): void
    {
        $this->uuidType = new UuidType();
    }

    public function testValidateWithValidUuidValueObject(): void
    {
        $uuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->uuidType->validate($uuid, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithValidNilUuidValueObject(): void
    {
        $uuid = Uuid::fromString('00000000-0000-0000-0000-000000000000');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->uuidType->validate($uuid, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithValidMaxUuidValueObject(): void
    {
        $uuid = Uuid::fromString('ffffffff-ffff-ffff-ffff-ffffffffffff');
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $result = $this->uuidType->validate($uuid, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithNonUuidObjectDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('UUID value must be a Uuid value object');

        $result = $this->uuidType->validate(123456, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithStringDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('UUID value must be a Uuid value object');

        $result = $this->uuidType->validate('12345678-1234-1234-1234-123456789abc', $context);

        $this->assertFalse($result);
    }

    public function testValidateWithArrayDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('UUID value must be a Uuid value object');

        $result = $this->uuidType->validate(['uuid' => '12345678-1234-1234-1234-123456789abc'], $context);

        $this->assertFalse($result);
    }

    public function testValidateWithNullDatum(): void
    {
        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->once())->method('addError')
            ->with('UUID value must be a Uuid value object');

        $result = $this->uuidType->validate(null, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithoutContext(): void
    {
        $uuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');

        $result = $this->uuidType->validate($uuid, null);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidDatumAndNullContext(): void
    {
        $result = $this->uuidType->validate(123456, null);

        $this->assertFalse($result);
    }

    public function testNormalizeWithUuidValueObject(): void
    {
        $uuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');

        $result = $this->uuidType->normalize($uuid);

        $this->assertIsString($result);
        $this->assertSame(16, strlen($result)); // 16 bytes

        // Verify it's the correct binary representation
        $expectedBinary = hex2bin('12345678123412341234123456789abc');
        $this->assertSame($expectedBinary, $result);
    }

    public function testNormalizeWithUppercaseUuidValueObject(): void
    {
        $uuid = Uuid::fromString('12345678-1234-1234-1234-123456789ABC');

        $result = $this->uuidType->normalize($uuid);

        $this->assertIsString($result);
        $this->assertSame(16, strlen($result));

        // Should handle uppercase correctly
        $expectedBinary = hex2bin('12345678123412341234123456789ABC');
        $this->assertSame($expectedBinary, $result);
    }

    public function testNormalizeWithNilUuidValueObject(): void
    {
        $uuid = Uuid::fromString('00000000-0000-0000-0000-000000000000');

        $result = $this->uuidType->normalize($uuid);

        $this->assertIsString($result);
        $this->assertSame(16, strlen($result));
        $this->assertSame(str_repeat("\x00", 16), $result);
    }

    public function testNormalizeWithMaxUuidValueObject(): void
    {
        $uuid = Uuid::fromString('ffffffff-ffff-ffff-ffff-ffffffffffff');

        $result = $this->uuidType->normalize($uuid);

        $this->assertIsString($result);
        $this->assertSame(16, strlen($result));
        $this->assertSame(str_repeat("\xff", 16), $result);
    }

    public function testDenormalizeWithValidBinary(): void
    {
        $binary = hex2bin('12345678123412341234123456789abc');

        $result = $this->uuidType->denormalize($binary);

        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame('12345678-1234-1234-1234-123456789abc', $result->toString());
    }

    public function testDenormalizeWithNilBinary(): void
    {
        $binary = str_repeat("\x00", 16);

        $result = $this->uuidType->denormalize($binary);

        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame('00000000-0000-0000-0000-000000000000', $result->toString());
    }

    public function testDenormalizeWithMaxBinary(): void
    {
        $binary = str_repeat("\xff", 16);

        $result = $this->uuidType->denormalize($binary);

        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', $result->toString());
    }

    public function testDenormalizeWithRandomBinary(): void
    {
        $binary = hex2bin('a1b2c3d4e5f6789012345678901234ab');

        $result = $this->uuidType->denormalize($binary);

        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame('a1b2c3d4-e5f6-7890-1234-5678901234ab', $result->toString());
    }

    public function testNormalizeAndDenormalizeRoundTrip(): void
    {
        $originalUuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');

        $normalized = $this->uuidType->normalize($originalUuid);
        $denormalized = $this->uuidType->denormalize($normalized);

        $this->assertInstanceOf(Uuid::class, $denormalized);
        $this->assertSame($originalUuid->toString(), $denormalized->toString());
    }

    public function testNormalizeAndDenormalizeRoundTripWithUppercase(): void
    {
        $originalUuid = Uuid::fromString('12345678-1234-1234-1234-123456789ABC');

        $normalized = $this->uuidType->normalize($originalUuid);
        $denormalized = $this->uuidType->denormalize($normalized);

        // Should return lowercase
        $this->assertInstanceOf(Uuid::class, $denormalized);
        $this->assertSame('12345678-1234-1234-1234-123456789abc', $denormalized->toString());
    }

    public function testNormalizeAndDenormalizeRoundTripWithNil(): void
    {
        $originalUuid = Uuid::fromString('00000000-0000-0000-0000-000000000000');

        $normalized = $this->uuidType->normalize($originalUuid);
        $denormalized = $this->uuidType->denormalize($normalized);

        $this->assertInstanceOf(Uuid::class, $denormalized);
        $this->assertSame($originalUuid->toString(), $denormalized->toString());
    }

    public function testNormalizeAndDenormalizeRoundTripWithMax(): void
    {
        $originalUuid = Uuid::fromString('ffffffff-ffff-ffff-ffff-ffffffffffff');

        $normalized = $this->uuidType->normalize($originalUuid);
        $denormalized = $this->uuidType->denormalize($normalized);

        $this->assertInstanceOf(Uuid::class, $denormalized);
        $this->assertSame($originalUuid->toString(), $denormalized->toString());
    }

    public function testValidateAndNormalizeChain(): void
    {
        $uuid = Uuid::fromString('a1b2c3d4-e5f6-7890-1234-567890123456');
        $context = $this->createMock(ValidationContextInterface::class);

        $isValid = $this->uuidType->validate($uuid, $context);
        $this->assertTrue($isValid);

        $normalized = $this->uuidType->normalize($uuid);
        $this->assertIsString($normalized);
        $this->assertSame(16, strlen($normalized));
    }

    public function testBinaryIntegrityAfterNormalization(): void
    {
        $testUuids = [
            '12345678-1234-1234-1234-123456789abc',
            '00000000-0000-0000-0000-000000000000',
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'a1b2c3d4-e5f6-7890-1234-567890123456',
            'ABCDEF12-3456-7890-ABCD-EF1234567890',
        ];

        foreach ($testUuids as $uuidString) {
            $uuid = Uuid::fromString($uuidString);
            $normalized = $this->uuidType->normalize($uuid);
            $this->assertIsString($normalized);

            // Verify binary length
            $this->assertSame(16, strlen($normalized), "Failed for UUID: {$uuidString}");

            // Verify round trip
            $denormalized = $this->uuidType->denormalize($normalized);
            $this->assertInstanceOf(Uuid::class, $denormalized, "Round trip failed for UUID: {$uuidString}");
            $this->assertSame(strtolower($uuidString), $denormalized->toString(), "Round trip failed for UUID: {$uuidString}");
        }
    }

    public function testFormatConsistency(): void
    {
        $binary = hex2bin('123456789abcdef0123456789abcdef0');

        $result = $this->uuidType->denormalize($binary);
        $this->assertInstanceOf(Uuid::class, $result);

        // Check format pattern
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $result->toString());
    }

    public function testCaseInsensitiveValidation(): void
    {
        $lowerUuid = Uuid::fromString('abcdef12-3456-7890-abcd-ef1234567890');
        $upperUuid = Uuid::fromString('ABCDEF12-3456-7890-ABCD-EF1234567890');
        $mixedUuid = Uuid::fromString('AbCdEf12-3456-7890-AbCd-Ef1234567890');

        $context = $this->createMock(ValidationContextInterface::class);
        $context->expects($this->never())->method('addError');

        $this->assertTrue($this->uuidType->validate($lowerUuid, $context));
        $this->assertTrue($this->uuidType->validate($upperUuid, $context));
        $this->assertTrue($this->uuidType->validate($mixedUuid, $context));
    }

    public function testNormalizeAndDenormalizeRoundTripWithUuidValueObject(): void
    {
        $originalUuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');

        $normalized = $this->uuidType->normalize($originalUuid);
        $denormalized = $this->uuidType->denormalize($normalized);

        $this->assertInstanceOf(Uuid::class, $denormalized);
        $this->assertSame($originalUuid->toString(), $denormalized->toString());
        $this->assertSame($originalUuid->toBytes(), $denormalized->toBytes());
    }
}
