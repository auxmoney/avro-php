<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BinaryDecoderTest extends TestCase
{
    private BinaryDecoder $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->decoder = new BinaryDecoder();
        $this->stream = $this->createMock(ReadableStreamInterface::class);
    }

    #[DataProvider('decodeLongDataProvider')]
    public function testReadLong(int $expectedValue, string $encodedData): void
    {
        $bytes = str_split($encodedData);
        $this->stream->expects($this->exactly(count($bytes)))
            ->method('read')
            ->with(1)
            ->willReturnOnConsecutiveCalls(...$bytes);

        $result = $this->decoder->readLong($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testSkipLongWithSingleByte(): void
    {
        // Test skipping a single byte value
        $this->stream->expects($this->once())
            ->method('read')
            ->with(1)
            ->willReturn("\x00"); // No continuation bit

        $this->decoder->skipLong($this->stream);
        // No assertion needed - just verify no exception is thrown
    }

    public function testSkipLongWithMultipleBytes(): void
    {
        // Test skipping a multi-byte value
        $this->stream->expects($this->exactly(3))
            ->method('read')
            ->with(1)
            ->willReturnOnConsecutiveCalls("\x80", "\x80", "\x01"); // Has continuation bits

        $this->decoder->skipLong($this->stream);
        // No assertion needed - just verify no exception is thrown
    }

    public function testReadFloatWithValidValue(): void
    {
        // Test reading a float value (3.14)
        $floatBytes = pack('g', 3.14);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(4)
            ->willReturn($floatBytes);

        $result = $this->decoder->readFloat($this->stream);

        $this->assertEqualsWithDelta(3.14, $result, 0.01);
    }

    public function testReadFloatWithZero(): void
    {
        // Test reading zero float
        $floatBytes = pack('g', 0.0);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(4)
            ->willReturn($floatBytes);

        $result = $this->decoder->readFloat($this->stream);

        $this->assertEquals(0.0, $result);
    }

    public function testReadFloatWithNegativeValue(): void
    {
        // Test reading a negative float
        $floatBytes = pack('g', -2.5);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(4)
            ->willReturn($floatBytes);

        $result = $this->decoder->readFloat($this->stream);

        $this->assertEqualsWithDelta(-2.5, $result, 0.01);
    }

    public function testReadFloatWithInfinity(): void
    {
        // Test reading infinity
        $floatBytes = pack('g', INF);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(4)
            ->willReturn($floatBytes);

        $result = $this->decoder->readFloat($this->stream);

        $this->assertTrue(is_infinite($result));
    }

    public function testReadFloatWithNaN(): void
    {
        // Test reading NaN
        $floatBytes = pack('g', NAN);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(4)
            ->willReturn($floatBytes);

        $result = $this->decoder->readFloat($this->stream);

        $this->assertTrue(is_nan($result));
    }

    public function testReadDoubleWithValidValue(): void
    {
        // Test reading a double value (3.14159265359)
        $doubleBytes = pack('e', 3.14159265359);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(8)
            ->willReturn($doubleBytes);

        $result = $this->decoder->readDouble($this->stream);

        $this->assertEqualsWithDelta(3.14159265359, $result, 0.000000001);
    }

    public function testReadDoubleWithZero(): void
    {
        // Test reading zero double
        $doubleBytes = pack('e', 0.0);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(8)
            ->willReturn($doubleBytes);

        $result = $this->decoder->readDouble($this->stream);

        $this->assertEquals(0.0, $result);
    }

    public function testReadDoubleWithNegativeValue(): void
    {
        // Test reading a negative double
        $doubleBytes = pack('e', -2.71828182846);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(8)
            ->willReturn($doubleBytes);

        $result = $this->decoder->readDouble($this->stream);

        $this->assertEqualsWithDelta(-2.71828182846, $result, 0.000000001);
    }

    public function testReadDoubleWithInfinity(): void
    {
        // Test reading infinity
        $doubleBytes = pack('e', INF);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(8)
            ->willReturn($doubleBytes);

        $result = $this->decoder->readDouble($this->stream);

        $this->assertTrue(is_infinite($result));
    }

    public function testReadDoubleWithNaN(): void
    {
        // Test reading NaN
        $doubleBytes = pack('e', NAN);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(8)
            ->willReturn($doubleBytes);

        $result = $this->decoder->readDouble($this->stream);

        $this->assertTrue(is_nan($result));
    }

    public function testSkipLongWithMaxBytes(): void
    {
        // Test skipping a value that uses maximum number of bytes
        $this->stream->expects($this->exactly(10))
            ->method('read')
            ->with(1)
            ->willReturnOnConsecutiveCalls("\x80", "\x80", "\x80", "\x80", "\x80", "\x80", "\x80", "\x80", "\x80", "\x01");

        $this->decoder->skipLong($this->stream);
        // No assertion needed - just verify no exception is thrown
    }

    /**
     * @return array<int, array{0: int, 1: string}>
     */
    public static function decodeLongDataProvider(): array
    {
        return [
            // Negative values
            [(int) -9223372036854775808, "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x01"],
            [-(1 << 62), "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F"],
            [-(1 << 61), "\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x3F"],
            [-4294967295, "\xFD\xFF\xFF\xFF\x1F"],
            [-(1 << 24), "\xFF\xFF\xFF\x0F"],
            [-(1 << 16), "\xFF\xFF\x07"],
            [-255, "\xFD\x03"],
            [-128, "\xFF\x01"],
            [-127, "\xFD\x01"],
            [-10, "\x13"],
            [-3, "\x05"],
            [-2, "\x03"],
            [-1, "\x01"],

            // Zero and positive values
            [0, "\x00"],
            [1, "\x02"],
            [2, "\x04"],
            [3, "\x06"],
            [10, "\x14"],
            [127, "\xFE\x01"],
            [128, "\x80\x02"],
            [255, "\xFE\x03"],
            [1 << 16, "\x80\x80\x08"],
            [1 << 24, "\x80\x80\x80\x10"],
            [4294967295, "\xFE\xFF\xFF\xFF\x1F"],
            [1 << 61, "\x80\x80\x80\x80\x80\x80\x80\x80\x40"],
            [1 << 62, "\x80\x80\x80\x80\x80\x80\x80\x80\x80\x01"],
            [9223372036854775807, "\xFE\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x01"],
        ];
    }
}
