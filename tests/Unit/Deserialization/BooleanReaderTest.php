<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BooleanReader;
use PHPUnit\Framework\TestCase;

class BooleanReaderTest extends TestCase
{
    private BooleanReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new BooleanReader();
    }

    public function testReadWithTrueValue(): void
    {
        $this->stream->expects($this->once())
            ->method('read')
            ->with(1)
            ->willReturn("\1");

        $result = $this->reader->read($this->stream);

        $this->assertTrue($result);
    }

    public function testReadWithFalseValue(): void
    {
        $this->stream->expects($this->once())
            ->method('read')
            ->with(1)
            ->willReturn("\0");

        $result = $this->reader->read($this->stream);

        $this->assertFalse($result);
    }

    public function testReadWithNonZeroValue(): void
    {
        $this->stream->expects($this->once())
            ->method('read')
            ->with(1)
            ->willReturn("\xFF");

        $result = $this->reader->read($this->stream);

        $this->assertTrue($result);
    }

    public function testSkipWithValidOperation(): void
    {
        $this->stream->expects($this->once())
            ->method('skip')
            ->with(1);

        $this->reader->skip($this->stream);
    }
}
