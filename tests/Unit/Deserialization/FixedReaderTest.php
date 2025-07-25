<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\FixedReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class FixedReaderTest extends TestCase
{
    private FixedReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
    }

    public function testReadWithSmallSize(): void
    {
        $size = 4;
        $expectedData = "\x01\x02\x03\x04";
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($size)
            ->willReturn($expectedData);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedData, $result);
    }

    public function testReadWithLargeSize(): void
    {
        $size = 1024;
        $expectedData = str_repeat("\x00", $size);
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($size)
            ->willReturn($expectedData);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedData, $result);
    }

    public function testReadWithZeroSize(): void
    {
        $size = 0;
        $expectedData = '';
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($size)
            ->willReturn($expectedData);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedData, $result);
    }

    public function testReadWithBinaryData(): void
    {
        $size = 8;
        $expectedData = "\x00\x01\x02\x03\xFF\xFE\xFD\xFC";
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($size)
            ->willReturn($expectedData);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedData, $result);
    }

    public function testReadWithStreamException(): void
    {
        $size = 4;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($size)
            ->willThrowException(new RuntimeException('Stream error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream error');

        $this->reader->read($this->stream);
    }

    public function testSkipWithSmallSize(): void
    {
        $size = 4;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($size);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithLargeSize(): void
    {
        $size = 1024;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($size);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithZeroSize(): void
    {
        $size = 0;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($size);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithStreamException(): void
    {
        $size = 4;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($size)
            ->willThrowException(new RuntimeException('Skip error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Skip error');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleCalls(): void
    {
        $size = 8;
        $this->reader = new FixedReader($size);

        $this->stream->expects($this->exactly(3))
            ->method('skip')
            ->with($size);

        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
    }

    public function testConstructorWithNegativeSize(): void
    {
        // FixedReader constructor doesn't validate size, so negative values are allowed
        $reader = new FixedReader(-1);

        // Test that the instance works (even with negative size)
        $this->stream->expects($this->once())
            ->method('read')
            ->with(-1)
            ->willReturn('test');

        $reader->read($this->stream);
    }
}
