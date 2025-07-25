<?php



declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\MapReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MapReaderTest extends TestCase
{
    private MapReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $valueReader;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->valueReader = $this->createMock(ReaderInterface::class);

        $this->reader = new MapReader($this->valueReader, $this->decoder);
    }

    public function testReadWithEmptyMap(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->stream->expects($this->never())
            ->method('read');

        $this->valueReader->expects($this->never())
            ->method('read');

        $result = $this->reader->read($this->stream);

        $this->assertSame([], $result);
    }

    public function testReadWithSingleKeyValuePair(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $keyLength = strlen($key);

        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, $keyLength, 0);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($keyLength)
            ->willReturn($key);

        $this->valueReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($value);

        $result = $this->reader->read($this->stream);

        $this->assertSame([$key => $value], $result);
    }

    public function testReadWithMultipleKeyValuePairs(): void
    {
        $key1 = 'key1';
        $key2 = 'key2';
        $value1 = 'value1';
        $value2 = 'value2';
        $key1Length = strlen($key1);
        $key2Length = strlen($key2);

        $this->decoder->expects($this->exactly(4))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(2, $key1Length, $key2Length, 0);

        $this->stream->expects($this->exactly(2))
            ->method('read')
            ->willReturnOnConsecutiveCalls($key1, $key2);

        $this->valueReader->expects($this->exactly(2))
            ->method('read')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls($value1, $value2);

        $result = $this->reader->read($this->stream);

        $this->assertSame([$key1 => $value1, $key2 => $value2], $result);
    }

    public function testReadWithNegativeBlockCount(): void
    {
        $key = 'test_key';
        $value = 'test_value';
        $keyLength = strlen($key);
        $blockSize = 100;

        $this->decoder->expects($this->exactly(4))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-1, $blockSize, $keyLength, 0);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($keyLength)
            ->willReturn($key);

        $this->valueReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($value);

        $result = $this->reader->read($this->stream);

        $this->assertSame([$key => $value], $result);
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

    public function testReadWithStreamException(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, 5);

        $this->stream->expects($this->once())
            ->method('read')
            ->with(5)
            ->willThrowException(new RuntimeException('Stream error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream error');

        $this->reader->read($this->stream);
    }

    public function testReadWithValueReaderException(): void
    {
        $key = 'test_key';
        $keyLength = strlen($key);

        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, $keyLength);

        $this->stream->expects($this->once())
            ->method('read')
            ->with($keyLength)
            ->willReturn($key);

        $this->valueReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Value reader error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value reader error');

        $this->reader->read($this->stream);
    }

    public function testSkipWithEmptyMap(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->stream->expects($this->never())
            ->method('skip');

        $this->valueReader->expects($this->never())
            ->method('skip');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithSingleKeyValuePair(): void
    {
        $keyLength = 8;

        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, $keyLength, 0);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($keyLength);

        $this->valueReader->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleKeyValuePairs(): void
    {
        $key1Length = 4;
        $key2Length = 6;

        $this->decoder->expects($this->exactly(4))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(2, $key1Length, $key2Length, 0);

        $this->stream->expects($this->exactly(2))
            ->method('skip')
            ->willReturnOnConsecutiveCalls(null, null);

        $this->valueReader->expects($this->exactly(2))
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithNegativeBlockCount(): void
    {
        $blockSize = 100;

        $this->decoder->expects($this->exactly(3))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(-1, $blockSize, 0);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($blockSize);

        $this->valueReader->expects($this->never())
            ->method('skip');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithDecoderException(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Decoder error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Decoder error');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithStreamException(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, 5);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with(5)
            ->willThrowException(new RuntimeException('Stream error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stream error');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithValueReaderException(): void
    {
        $keyLength = 8;

        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(1, $keyLength);

        $this->stream->expects($this->once())
            ->method('skip')
            ->with($keyLength);

        $this->valueReader->expects($this->once())
            ->method('skip')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Value skip error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Value skip error');

        $this->reader->skip($this->stream);
    }
}
