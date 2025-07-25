<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\NullReader;
use PHPUnit\Framework\TestCase;

class NullReaderTest extends TestCase
{
    private NullReader $reader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->reader = new NullReader();
    }

    public function testReadAlwaysReturnsNull(): void
    {
        $this->stream->expects($this->never())
            ->method('read');

        $result = $this->reader->read($this->stream);

        $this->assertNull($result);
    }

    public function testSkipDoesNothing(): void
    {
        $this->stream->expects($this->never())
            ->method('skip');

        $this->reader->skip($this->stream);

        // No assertions needed as skip does nothing
    }

    public function testSkipWithMultipleCalls(): void
    {
        $this->stream->expects($this->never())
            ->method('skip');

        // Multiple skip calls should do nothing
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);
        $this->reader->skip($this->stream);

        // No assertions needed as multiple skip calls do nothing
    }
}
