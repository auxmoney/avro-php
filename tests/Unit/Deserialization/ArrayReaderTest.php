<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\ArrayReader;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use PHPUnit\Framework\TestCase;

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
}
