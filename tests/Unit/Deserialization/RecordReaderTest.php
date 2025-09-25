<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\RecordReader;
use PHPUnit\Framework\TestCase;

class RecordReaderTest extends TestCase
{
    private RecordReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private \Auxmoney\Avro\Deserialization\RecordPropertyReader&\PHPUnit\Framework\MockObject\MockObject $propertyReader1;
    private \Auxmoney\Avro\Deserialization\RecordPropertyReader&\PHPUnit\Framework\MockObject\MockObject $propertyReader2;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->propertyReader1 = $this->createMock(\Auxmoney\Avro\Deserialization\RecordPropertyReader::class);
        $this->propertyReader2 = $this->createMock(\Auxmoney\Avro\Deserialization\RecordPropertyReader::class);
        $this->reader = new RecordReader([$this->propertyReader1, $this->propertyReader2]);
    }

    public function testReadWithMultipleProperties(): void
    {
        $record = [];
        $this->propertyReader1->expects($this->once())
            ->method('read')
            ->with($this->stream, $this->isType('array'));
        $this->propertyReader2->expects($this->once())
            ->method('read')
            ->with($this->stream, $this->isType('array'));
        $result = $this->reader->read($this->stream);
        $this->assertIsArray($result);
    }

    public function testSkipWithMultipleProperties(): void
    {
        $this->propertyReader1->expects($this->once())
            ->method('skip')
            ->with($this->stream);
        $this->propertyReader2->expects($this->once())
            ->method('skip')
            ->with($this->stream);
        $this->reader->skip($this->stream);
    }

    public function testSkipWithNoProperties(): void
    {
        $reader = new RecordReader([]);
        $this->stream->expects($this->never())
            ->method('skip');

        $reader->skip($this->stream);
    }
}
