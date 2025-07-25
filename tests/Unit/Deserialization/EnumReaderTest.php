<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Deserialization\BinaryDecoder;
use Auxmoney\Avro\Deserialization\EnumReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class EnumReaderTest extends TestCase
{
    private EnumReader $reader;
    private BinaryDecoder&\PHPUnit\Framework\MockObject\MockObject $decoder;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    /** @var array<string> */
    private array $validValues;

    protected function setUp(): void
    {
        $this->decoder = $this->createMock(BinaryDecoder::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->validValues = ['RED', 'GREEN', 'BLUE'];
        $this->reader = new EnumReader($this->validValues, $this->decoder);
    }

    public function testReadWithValidIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(1);

        $result = $this->reader->read($this->stream);

        $this->assertSame('GREEN', $result);
    }

    public function testReadWithFirstIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $result = $this->reader->read($this->stream);

        $this->assertSame('RED', $result);
    }

    public function testReadWithLastIndex(): void
    {
        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2);

        $result = $this->reader->read($this->stream);

        $this->assertSame('BLUE', $result);
    }

    public function testReadWithInvalidPositiveIndex(): void
    {
        $invalidIndex = 5; // Out of bounds

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($invalidIndex);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid enum index: {$invalidIndex}");

        $this->reader->read($this->stream);
    }

    public function testReadWithNegativeIndex(): void
    {
        $invalidIndex = -1;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($invalidIndex);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid enum index: {$invalidIndex}");

        $this->reader->read($this->stream);
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

    public function testReadWithEmptyEnumValues(): void
    {
        $emptyReader = new EnumReader([], $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid enum index: 0');

        $emptyReader->read($this->stream);
    }

    public function testReadWithSingleValueEnum(): void
    {
        $singleValueReader = new EnumReader(['ONLY_VALUE'], $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $result = $singleValueReader->read($this->stream);

        $this->assertSame('ONLY_VALUE', $result);
    }

    public function testReadWithSingleValueEnumInvalidIndex(): void
    {
        $singleValueReader = new EnumReader(['ONLY_VALUE'], $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(1); // Invalid for single-value enum

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid enum index: 1');

        $singleValueReader->read($this->stream);
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

    public function testReadWithBoundaryValues(): void
    {
        // Test exactly at the boundary
        $maxValidIndex = count($this->validValues) - 1;

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($maxValidIndex);

        $result = $this->reader->read($this->stream);

        $this->assertSame($this->validValues[$maxValidIndex], $result);
    }

    public function testReadWithJustOverBoundary(): void
    {
        // Test one index over the boundary
        $invalidIndex = count($this->validValues);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn($invalidIndex);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Invalid enum index: {$invalidIndex}");

        $this->reader->read($this->stream);
    }

    public function testReadWithUnicodeEnumValues(): void
    {
        $unicodeValues = ['🔴', '🟢', '🔵'];
        $unicodeReader = new EnumReader($unicodeValues, $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(1);

        $result = $unicodeReader->read($this->stream);

        $this->assertSame('🟢', $result);
    }

    public function testReadWithEmptyStringValue(): void
    {
        $valuesWithEmpty = ['', 'NOT_EMPTY', 'ALSO_NOT_EMPTY'];
        $emptyStringReader = new EnumReader($valuesWithEmpty, $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(0);

        $result = $emptyStringReader->read($this->stream);

        $this->assertSame('', $result);
    }

    public function testReadWithSpecialCharacterValues(): void
    {
        $specialValues = ['value with spaces', 'value\nwith\nnewlines', 'value\twith\ttabs'];
        $specialReader = new EnumReader($specialValues, $this->decoder);

        $this->decoder->expects($this->once())
            ->method('readLong')
            ->with($this->stream)
            ->willReturn(2);

        $result = $specialReader->read($this->stream);

        $this->assertSame('value\twith\ttabs', $result);
    }
}
