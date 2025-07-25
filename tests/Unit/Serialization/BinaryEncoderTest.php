<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BinaryEncoderTest extends TestCase
{
    private BinaryEncoder $encoder;
    private WritableStreamInterface $stream;

    protected function setUp(): void
    {
        $this->encoder = new BinaryEncoder();
        $this->stream = $this->createMock(WritableStreamInterface::class);
    }

    #[DataProvider('encodeLongDataProvider')]
    public function testWriteLong(int $value, string $expectedEncoding): void
    {
        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedEncoding);

        $this->encoder->writeLong($this->stream, $value);
    }

    public function testWriteStringWithEmptyString(): void
    {
        $value = '';
        $expectedLengthEncoding = "\x00"; // length 0 encoded as long

        $this->stream->expects($this->once())
            ->method('write')
            ->with($expectedLengthEncoding);

        $this->encoder->writeString($this->stream, $value);
    }

    public function testWriteStringWithNonEmptyString(): void
    {
        $value = 'Hello, World!';
        $length = strlen($value);
        $expectedLengthEncoding = "\x1A"; // length 13 encoded as long

        $this->stream->expects($this->exactly(2))
            ->method('write');

        $this->encoder->writeString($this->stream, $value);
    }

    public function testEncodeFloat(): void
    {
        $value = 123.45;
        $result = $this->encoder->encodeFloat($value);

        // Verify it's a 4-byte float encoding
        $this->assertSame(4, strlen($result));

        // Verify we can decode it back to a similar value
        $decoded = unpack('g', $result)[1];
        $this->assertEqualsWithDelta($value, $decoded, 0.001);
    }

    public function testEncodeDouble(): void
    {
        $value = 123.456789;
        $result = $this->encoder->encodeDouble($value);

        // Verify it's an 8-byte double encoding
        $this->assertSame(8, strlen($result));

        // Verify we can decode it back to a similar value
        $decoded = unpack('e', $result)[1];
        $this->assertEqualsWithDelta($value, $decoded, 0.000001);
    }

    public static function encodeLongDataProvider(): array
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
