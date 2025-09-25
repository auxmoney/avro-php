<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\FloatReader;
use PHPUnit\Framework\TestCase;

class FloatReaderTest extends TestCase
{
    private FloatReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new FloatReader($this->decoder);
    }

    public function testReadWithPositiveValue(): void
    {
        $expectedValue = 123.45;

        $this->decoder->expects($this->once())
            ->method('readFloat')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithNegativeValue(): void
    {
        $expectedValue = -123.45;

        $this->decoder->expects($this->once())
            ->method('readFloat')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithZeroValue(): void
    {
        $this->decoder->expects($this->once())
            ->method('readFloat')
            ->with($this->stream)
            ->willReturn(0.0);

        $result = $this->reader->read($this->stream);

        $this->assertSame(0.0, $result);
    }

    public function testReadWithInfinity(): void
    {
        $expectedValue = INF;

        $this->decoder->expects($this->once())
            ->method('readFloat')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithNaN(): void
    {
        $expectedValue = NAN;

        $this->decoder->expects($this->once())
            ->method('readFloat')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertNan($result);
    }

    public function testSkipWithValidOperation(): void
    {
        $this->stream->expects($this->once())
            ->method('skip')
            ->with(4);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleCalls(): void
    {
        $this->stream->expects($this->exactly(3))
            ->method('skip')
            ->with(4);

        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
    }
}
