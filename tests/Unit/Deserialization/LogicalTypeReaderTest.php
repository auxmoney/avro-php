<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\LogicalTypeReader;
use PHPUnit\Framework\TestCase;

class LogicalTypeReaderTest extends TestCase
{
    private LogicalTypeReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $rawReader;
    private LogicalTypeInterface&\PHPUnit\Framework\MockObject\MockObject $logicalType;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->rawReader = $this->createMock(ReaderInterface::class);
        $this->logicalType = $this->createMock(LogicalTypeInterface::class);

        $this->reader = new LogicalTypeReader($this->rawReader, $this->logicalType);
    }

    public function testReadWithValidData(): void
    {
        $rawValue = 123;
        $denormalizedValue = '2023-01-01';

        $this->rawReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($rawValue);

        $this->logicalType->expects($this->once())
            ->method('denormalize')
            ->with($rawValue)
            ->willReturn($denormalizedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($denormalizedValue, $result);
    }

    public function testReadWithNullValue(): void
    {
        $rawValue = null;
        $denormalizedValue = null;

        $this->rawReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($rawValue);

        $this->logicalType->expects($this->once())
            ->method('denormalize')
            ->with($rawValue)
            ->willReturn($denormalizedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($denormalizedValue, $result);
    }

    public function testReadWithStringValue(): void
    {
        $rawValue = 'test_string';
        $denormalizedValue = 'TEST_STRING';

        $this->rawReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($rawValue);

        $this->logicalType->expects($this->once())
            ->method('denormalize')
            ->with($rawValue)
            ->willReturn($denormalizedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($denormalizedValue, $result);
    }

    public function testReadWithArrayValue(): void
    {
        $rawValue = [1, 2, 3];
        $denormalizedValue = ['a', 'b', 'c'];

        $this->rawReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($rawValue);

        $this->logicalType->expects($this->once())
            ->method('denormalize')
            ->with($rawValue)
            ->willReturn($denormalizedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($denormalizedValue, $result);
    }

    public function testSkipWithValidOperation(): void
    {
        $this->rawReader->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->logicalType->expects($this->never())
            ->method('denormalize');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleCalls(): void
    {
        $this->rawReader->expects($this->exactly(3))
            ->method('skip')
            ->with($this->stream);

        $this->logicalType->expects($this->never())
            ->method('denormalize');

        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
    }
}
