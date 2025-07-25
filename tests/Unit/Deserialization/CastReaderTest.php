<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\CastReader;
use PHPUnit\Framework\TestCase;

class CastReaderTest extends TestCase
{
    private CastReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $typeReader;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->typeReader = $this->createMock(ReaderInterface::class);
    }

    public function testReadWithStringToIntCast(): void
    {
        $rawValue = '123';
        $castedValue = 123;

        $this->reader = new CastReader($this->typeReader, intval(...));

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($rawValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($castedValue, $result);
    }

    public function testSkipWithValidOperation(): void
    {
        $cast = fn ($value) => $value;

        $this->reader = new CastReader($this->typeReader, $cast);

        $this->typeReader->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }
}
