<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;
use Auxmoney\Avro\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    public function testFromStringWithValidUuid(): void
    {
        $uuidString = '12345678-1234-1234-1234-123456789abc';
        $uuid = Uuid::fromString($uuidString);

        $this->assertSame($uuidString, $uuid->toString());
        $this->assertSame(hex2bin('12345678123412341234123456789abc'), $uuid->toBytes());
    }

    public function testFromStringWithUppercaseUuid(): void
    {
        $uuidString = '12345678-1234-1234-1234-123456789ABC';
        $uuid = Uuid::fromString($uuidString);

        // toString should return lowercase
        $this->assertSame('12345678-1234-1234-1234-123456789abc', $uuid->toString());
        $this->assertSame(hex2bin('12345678123412341234123456789ABC'), $uuid->toBytes());
    }

    public function testFromBytesWithValidBytes(): void
    {
        $bytes = hex2bin('12345678123412341234123456789abc');
        $this->assertIsString($bytes); // Assert hex2bin didn't return false
        $uuid = Uuid::fromBytes($bytes);

        $this->assertSame('12345678-1234-1234-1234-123456789abc', $uuid->toString());
        $this->assertSame($bytes, $uuid->toBytes());
    }

    public function testFromStringWithInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format. Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

        Uuid::fromString('invalid-uuid-format');
    }

    public function testFromStringWithMissingHyphens(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format. Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');

        Uuid::fromString('12345678123412341234123456789abc');
    }

    public function testFromBytesWithInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID bytes must be exactly 16 bytes long');

        Uuid::fromBytes('invalid');
    }

    public function testFromBytesWithEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID bytes must be exactly 16 bytes long');

        Uuid::fromBytes('');
    }

    public function testFromBytesTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UUID bytes must be exactly 16 bytes long');

        Uuid::fromBytes(str_repeat('x', 17));
    }

    public function testNilUuid(): void
    {
        $nilString = '00000000-0000-0000-0000-000000000000';
        $nilBytes = str_repeat("\x00", 16);

        $uuidFromString = Uuid::fromString($nilString);
        $uuidFromBytes = Uuid::fromBytes($nilBytes);

        $this->assertSame($nilString, $uuidFromString->toString());
        $this->assertSame($nilBytes, $uuidFromString->toBytes());
        $this->assertSame($nilString, $uuidFromBytes->toString());
        $this->assertSame($nilBytes, $uuidFromBytes->toBytes());
    }

    public function testMaxUuid(): void
    {
        $maxString = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
        $maxBytes = str_repeat("\xff", 16);

        $uuidFromString = Uuid::fromString($maxString);
        $uuidFromBytes = Uuid::fromBytes($maxBytes);

        $this->assertSame($maxString, $uuidFromString->toString());
        $this->assertSame($maxBytes, $uuidFromString->toBytes());
        $this->assertSame($maxString, $uuidFromBytes->toString());
        $this->assertSame($maxBytes, $uuidFromBytes->toBytes());
    }

    public function testRoundTripConversion(): void
    {
        $testCases = [
            '12345678-1234-1234-1234-123456789abc',
            '00000000-0000-0000-0000-000000000000',
            'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'a1b2c3d4-e5f6-7890-1234-567890123456',
        ];

        foreach ($testCases as $originalString) {
            // String -> Bytes -> String
            $uuid1 = Uuid::fromString($originalString);
            $bytes = $uuid1->toBytes();
            $uuid2 = Uuid::fromBytes($bytes);
            $finalString = $uuid2->toString();

            $this->assertSame($originalString, $finalString, "Round trip failed for: {$originalString}");
        }
    }

    public function testReadonlyProperty(): void
    {
        $uuid = Uuid::fromString('12345678-1234-1234-1234-123456789abc');
        $expectedBytes = hex2bin('12345678123412341234123456789abc');

        $this->assertSame($expectedBytes, $uuid->bytes);
    }
}
