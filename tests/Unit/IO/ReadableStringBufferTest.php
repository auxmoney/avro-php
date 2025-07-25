<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\IO;

use Auxmoney\Avro\Exceptions\StreamReadException;
use Auxmoney\Avro\IO\ReadableStringBuffer;
use PHPUnit\Framework\TestCase;

class ReadableStringBufferTest extends TestCase
{
    public function testReadSingleByte(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $result = $buffer->read(1);

        $this->assertSame('H', $result);
    }

    public function testReadMultipleBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello World');

        $result = $buffer->read(5);

        $this->assertSame('Hello', $result);
    }

    public function testReadEntireBuffer(): void
    {
        $data = 'Test data';
        $buffer = new ReadableStringBuffer($data);

        $result = $buffer->read(strlen($data));

        $this->assertSame($data, $result);
    }

    public function testReadSequentially(): void
    {
        $buffer = new ReadableStringBuffer('Hello World');

        $first = $buffer->read(5);
        $second = $buffer->read(6);

        $this->assertSame('Hello', $first);
        $this->assertSame(' World', $second);
    }

    public function testSkipSingleByte(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $buffer->skip(1);
        $result = $buffer->read(4);

        $this->assertSame('ello', $result);
    }

    public function testSkipMultipleBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello World');

        $buffer->skip(6);
        $result = $buffer->read(5);

        $this->assertSame('World', $result);
    }

    public function testSkipAndReadCombined(): void
    {
        $buffer = new ReadableStringBuffer('Hello World');

        $buffer->skip(2);
        $read1 = $buffer->read(3);
        $buffer->skip(1);
        $read2 = $buffer->read(4);

        $this->assertSame('llo', $read1);
        $this->assertSame('Worl', $read2);
    }

    public function testReadWithNegativeCount(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Negative number of bytes is not allowed');

        $buffer->read(-1);
    }

    public function testSkipWithNegativeCount(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Negative number of bytes is not allowed');

        $buffer->skip(-1);
    }

    public function testReadBeyondBufferEnd(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 10 bytes but only 5 bytes remain');

        $buffer->read(10);
    }

    public function testSkipBeyondBufferEnd(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 10 bytes but only 5 bytes remain');

        $buffer->skip(10);
    }

    public function testReadAfterBufferExhausted(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        // Read all available bytes
        $buffer->read(5);

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 1 bytes but only 0 bytes remain');

        $buffer->read(1);
    }

    public function testSkipAfterBufferExhausted(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        // Skip all available bytes
        $buffer->skip(5);

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 1 bytes but only 0 bytes remain');

        $buffer->skip(1);
    }

    public function testReadZeroBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $result = $buffer->read(0);

        $this->assertSame('', $result);
    }

    public function testSkipZeroBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        // Should not throw an exception
        $buffer->skip(0);

        $result = $buffer->read(5);
        $this->assertSame('Hello', $result);
    }

    public function testReadExactRemainingBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $buffer->skip(2);
        $result = $buffer->read(3);

        $this->assertSame('llo', $result);
    }

    public function testSkipExactRemainingBytes(): void
    {
        $buffer = new ReadableStringBuffer('Hello');

        $buffer->skip(2);
        $buffer->skip(3);

        // Should not throw an exception
        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 1 bytes but only 0 bytes remain');

        $buffer->read(1);
    }

    public function testReadWithEmptyBuffer(): void
    {
        $buffer = new ReadableStringBuffer('');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 1 bytes but only 0 bytes remain');

        $buffer->read(1);
    }

    public function testSkipWithEmptyBuffer(): void
    {
        $buffer = new ReadableStringBuffer('');

        $this->expectException(StreamReadException::class);
        $this->expectExceptionMessage('Stream exhausted: requested 1 bytes but only 0 bytes remain');

        $buffer->skip(1);
    }

    public function testReadWithBinaryData(): void
    {
        $binaryData = "\x00\x01\x02\x03\xFF\xFE\xFD";
        $buffer = new ReadableStringBuffer($binaryData);

        $result = $buffer->read(4);

        $this->assertSame("\x00\x01\x02\x03", $result);
    }

    public function testReadWithUnicodeData(): void
    {
        $unicodeData = 'Hello 世界 🌍';
        $buffer = new ReadableStringBuffer($unicodeData);

        $result = $buffer->read(6);

        $this->assertSame('Hello ', $result);
    }
}
