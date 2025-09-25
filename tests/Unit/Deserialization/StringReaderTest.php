<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\StringReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StringReaderTest extends TestCase
{
    private StringReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new StringReader($this->decoder);
    }

    public function testReadWithEmptyString(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->stream->expects($this->never())
            ->method('read');

        $result = $this->reader->read($this->stream);

        $this->assertSame('', $result);
    }

    public function testReadWithValidString(): void
    {
        $expectedString = 'Hello, World!';
        $length = strlen($expectedString);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($length)
            ->willReturn($expectedString);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedString, $result);
    }

    public function testReadWithUnicodeString(): void
    {
        $unicodeString = '🚀 Hello 世界 🌍';
        $length = strlen($unicodeString); // Byte length, not character length

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($length)
            ->willReturn($unicodeString);

        $result = $this->reader->read($this->stream);

        $this->assertSame($unicodeString, $result);
    }

    public function testReadWithBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD\xFC";
        $length = strlen($binaryData);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($length)
            ->willReturn($binaryData);

        $result = $this->reader->read($this->stream);

        $this->assertSame($binaryData, $result);
    }

    public function testReadWithLargeString(): void
    {
        $largeString = str_repeat('A', 10000);
        $length = strlen($largeString);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($length)
            ->willReturn($largeString);

        $result = $this->reader->read($this->stream);

        $this->assertSame($largeString, $result);
    }

    public function testReadWithTruncatedData(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(10);

        // Stream returns less data than expected
        $this->stream->expects($this->once())
            ->method('read')
            ->with(10)
            ->willReturn('short'); // Only 5 bytes instead of 10

        // The reader should return whatever the stream provides
        $result = $this->reader->read($this->stream);

        $this->assertSame('short', $result);
    }

    public function testReadWithNegativeLength(): void
    {
        // This is a malformed case - negative string length
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(-5);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(-5)
            ->willThrowException(new RuntimeException('Invalid read length'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid read length');

        $this->reader->read($this->stream);
    }

    public function testSkipWithEmptyString(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(0);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithValidLength(): void
    {
        $length = 25;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($length);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithNegativeLength(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(-10);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(-10)
            ->willThrowException(new RuntimeException('Invalid skip length'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid skip length');

        $this->reader->skip($this->stream);
    }

    public function testReadWithNullBytes(): void
    {
        $stringWithNulls = "hello\x00world\x00";
        $length = strlen($stringWithNulls);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($length);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($length)
            ->willReturn($stringWithNulls);

        $result = $this->reader->read($this->stream);

        $this->assertSame($stringWithNulls, $result);
    }
}
