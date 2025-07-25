<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\LongReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LongReaderTest extends TestCase
{
    private LongReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new LongReader($this->decoder);
    }

    public function testReadWithPositiveValue(): void
    {
        $expectedValue = 12345;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithNegativeValue(): void
    {
        $expectedValue = -12345;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithZeroValue(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $result = $this->reader->read($this->stream);

        $this->assertSame(0, $result);
    }

    public function testReadWithLargeValue(): void
    {
        $expectedValue = PHP_INT_MAX;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
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

    public function testSkipWithValidOperation(): void
    {
        $this->decoder->expects($this->once())
            ->method('skipLong')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithDecoderException(): void
    {
        $this->decoder->expects($this->once())
            ->method('skipLong')
            ->with($this->stream)
            ->willThrowException(new RuntimeException('Skip error'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Skip error');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleCalls(): void
    {
        $this->decoder->expects($this->exactly(3))
            ->method('skipLong')
            ->with($this->stream);

        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
    }
}
