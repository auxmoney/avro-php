<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\ArrayReader;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ArrayReaderTest extends TestCase
{
    private ArrayReader $reader;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $itemReader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->itemReader = $this->createMock(ReaderInterface::class);
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new ArrayReader($this->itemReader, $this->decoder);
    }

    public function testReadWithEmptyArray(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0); // Block count 0 means end of array

        $this->itemReader->expects($this->never())
            ->method('read');

        $result = $this->reader->read($this->stream);

        $this->assertSame([], $result);
    }

    public function testReadWithSingleBlock(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(2, 0);

        $this->itemReader->expects($this->exactly(2))
            ->method('read')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls('item1', 'item2');

        $result = $this->reader->read($this->stream);

        $this->assertSame(['item1', 'item2'], $result);
    }

    public function testReadWithMultipleBlocks(): void
    {
        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(2, 1, 0);

        $this->itemReader->expects($this->exactly(3))
            ->method('read')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls('item1', 'item2', 'item3');

        $result = $this->reader->read($this->stream);

        $this->assertSame(['item1', 'item2', 'item3'], $result);
    }

    public function testReadWithNegativeBlockCount(): void
    {
        // Negative block count indicates block size follows
        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-2, 10, 0);

        $this->itemReader->expects($this->exactly(2))
            ->method('read')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls('item1', 'item2');

        $result = $this->reader->read($this->stream);

        $this->assertSame(['item1', 'item2'], $result);
    }

    public function testReadWithMixedBlockTypes(): void
    {
        $this->decoder->expects($this->exactly(4))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, -2, 15, 0);

        $this->itemReader->expects($this->exactly(3))
            ->method('read')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls('item1', 'item2', 'item3');

        $result = $this->reader->read($this->stream);

        $this->assertSame(['item1', 'item2', 'item3'], $result);
    }

    public function testReadWithDecoderException(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Decoder error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decoder error');

        $this->reader->read($this->stream);
    }

    public function testReadWithItemReaderException(): void
    {
        // When an exception occurs during item reading, the process stops immediately
        // so readLong is only called once (for the block count), not twice (no terminator read)
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2); // Block count

        $this->itemReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Item reader error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Item reader error');

        $this->reader->read($this->stream);
    }

    public function testSkipWithEmptyArray(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->itemReader->expects($this->never())
            ->method('skip');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithSingleBlock(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(3, 0);

        $this->itemReader->expects($this->exactly(3))
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithNegativeBlockCount(): void
    {
        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-3, 15, 0);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(15); // Block size

        $this->reader->skip($this->stream);
    }

    public function testSkipWithNegativeBlockCountAndBlockSize(): void
    {
        $blockSize = 25;

        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-2, $blockSize, 0);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($blockSize);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMixedBlockTypes(): void
    {
        $this->decoder->expects($this->exactly(4))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(2, -3, 30, 0);

        $this->itemReader->expects($this->exactly(2))
            ->method('skip')
            ->with($this->stream);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(30);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithItemReaderException(): void
    {
        // When an exception occurs during item skipping, the process stops immediately
        // so readLong is only called once (for the block count), not twice (no terminator read)
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2); // Block count

        $this->itemReader->expects($this->once())
            ->method('skip')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Skip error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Skip error');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithStreamException(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-2, 20);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(20)
            ->willThrowException(new RuntimeException('Stream skip error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream skip error');

        $this->reader->skip($this->stream);
    }
}
