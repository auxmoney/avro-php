<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\UnionReader;
use Auxmoney\Avro\Exceptions\SchemaMismatchException;
use PHPUnit\Framework\TestCase;

class UnionReaderTest extends TestCase
{
    private UnionReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $branchReader1;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $branchReader2;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->branchReader1 = $this->createMock(ReaderInterface::class);
        $this->branchReader2 = $this->createMock(ReaderInterface::class);

        $this->reader = new UnionReader([$this->branchReader1, $this->branchReader2], $this->decoder);
    }

    public function testReadWithFirstBranch(): void
    {
        $expectedValue = 'test value';

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->branchReader1->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->branchReader2->expects($this->never())
            ->method('read');

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithSecondBranch(): void
    {
        $expectedValue = 123;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(1);

        $this->branchReader1->expects($this->never())
            ->method('read');

        $this->branchReader2->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $result = $this->reader->read($this->stream);

        $this->assertSame($expectedValue, $result);
    }

    public function testReadWithInvalidBranchIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2);

        $this->branchReader1->expects($this->never())
            ->method('read');

        $this->branchReader2->expects($this->never())
            ->method('read');

        $this->expectException(SchemaMismatchException::class);
        $this->expectExceptionMessage('Invalid branch index: 2');

        $this->reader->read($this->stream);
    }

    public function testReadWithNegativeBranchIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(-1);

        $this->branchReader1->expects($this->never())
            ->method('read');

        $this->branchReader2->expects($this->never())
            ->method('read');

        $this->expectException(SchemaMismatchException::class);
        $this->expectExceptionMessage('Invalid branch index: -1');

        $this->reader->read($this->stream);
    }

    public function testSkipWithFirstBranch(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->branchReader1->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->branchReader2->expects($this->never())
            ->method('skip');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithSecondBranch(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(1);

        $this->branchReader1->expects($this->never())
            ->method('skip');

        $this->branchReader2->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
    }

    public function testSkipWithInvalidBranchIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2);

        $this->branchReader1->expects($this->never())
            ->method('skip');

        $this->branchReader2->expects($this->never())
            ->method('skip');

        $this->expectException(SchemaMismatchException::class);
        $this->expectExceptionMessage('Invalid branch index: 2');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithNegativeBranchIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(-1);

        $this->branchReader1->expects($this->never())
            ->method('skip');

        $this->branchReader2->expects($this->never())
            ->method('skip');

        $this->expectException(SchemaMismatchException::class);
        $this->expectExceptionMessage('Invalid branch index: -1');

        $this->reader->skip($this->stream);
    }

    public function testSkipWithMultipleCalls(): void
    {
        $this->decoder->expects($this->exactly(2))
            ->method('readLong')
            ->with($this->stream)
            ->willReturnOnConsecutiveCalls(0, 1);

        $this->branchReader1->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->branchReader2->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
    }
}
