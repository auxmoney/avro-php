<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use ArrayIterator;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\MapWriter;
use Auxmoney\Avro\Serialization\StringWriter;
use PHPUnit\Framework\TestCase;
use Traversable;

class MapWriterTest extends TestCase
{
    private MapWriter $mapWriter;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private StringWriter&\PHPUnit\Framework\MockObject\MockObject $valueWriter;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->valueWriter = $this->createMock(StringWriter::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->mapWriter = new MapWriter($this->valueWriter, $this->encoder, 2, false);
    }

    public function testWriteWithArray(): void
    {
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->encoder->expects($this->exactly(3))
            ->method('writeLong');

        $this->encoder->expects($this->exactly(2))
            ->method('writeString');

        $this->valueWriter->expects($this->exactly(2))
            ->method('write');

        $this->mapWriter->write($data, $this->stream);
    }

    public function testWriteWithEmptyArray(): void
    {
        $data = [];

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong');

        $this->encoder->expects($this->never())->method('writeString');
        $this->valueWriter->expects($this->never())->method('write');

        $this->mapWriter->write($data, $this->stream);
    }

    public function testWriteWithBlockSizes(): void
    {
        $mapWriter = new MapWriter($this->valueWriter, $this->encoder, 2, true);
        $data = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

        $this->encoder->expects($this->exactly(4))
            ->method('writeLong');

        $this->encoder->expects($this->exactly(5))
            ->method('writeString');

        $this->valueWriter->expects($this->exactly(3))
            ->method('write');

        $mapWriter->write($data, $this->stream);
    }

    public function testWriteWithTraversable(): void
    {
        $data = new ArrayIterator(['key1' => 'value1', 'key2' => 'value2']);

        $this->encoder->expects($this->exactly(3))
            ->method('writeLong');

        $this->encoder->expects($this->exactly(2))
            ->method('writeString');

        $this->valueWriter->expects($this->exactly(2))
            ->method('write');

        $this->mapWriter->write($data, $this->stream);
    }

    public function testWriteWithBlockCountZero(): void
    {
        $mapWriter = new MapWriter($this->valueWriter, $this->encoder, 0, false);
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong');

        $this->encoder->expects($this->exactly(2))
            ->method('writeString');

        $this->valueWriter->expects($this->exactly(2))
            ->method('write');

        $mapWriter->write($data, $this->stream);
    }

    public function testWriteWithBlockCountZeroEmptyArray(): void
    {
        $mapWriter = new MapWriter($this->valueWriter, $this->encoder, 0, false);
        $data = [];

        $this->encoder->expects($this->exactly(1))
            ->method('writeLong');

        $this->encoder->expects($this->never())->method('writeString');
        $this->valueWriter->expects($this->never())->method('write');

        $mapWriter->write($data, $this->stream);
    }

    public function testValidateWithValidArray(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];

        $this->valueWriter->method('validate')
            ->willReturnMap([['value1', null, true], ['value2', null, true]]);

        $result = $this->mapWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithValidTraversable(): void
    {
        $data = new ArrayIterator(['key1' => 'value1']);

        $this->valueWriter->method('validate')
            ->with('value1', null)
            ->willReturn(true);

        $result = $this->mapWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidType(): void
    {
        $data = 'not an array';

        $result = $this->mapWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithNonCountableTraversable(): void
    {
        $data = $this->createMock(Traversable::class);

        $result = $this->mapWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidKeyType(): void
    {
        $data = [123 => 'value1'];

        $result = $this->mapWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidValue(): void
    {
        $data = ['key1' => 'value1'];

        $this->valueWriter->method('validate')
            ->with('value1', null)
            ->willReturn(false);

        $result = $this->mapWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithContext(): void
    {
        $data = ['key1' => 'value1', 'key2' => 'value2'];
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->exactly(2))
            ->method('pushPath');

        $context->expects($this->exactly(2))
            ->method('popPath');

        $this->valueWriter->method('validate')
            ->willReturnMap([['value1', $context, true], ['value2', $context, true]]);

        $result = $this->mapWriter->validate($data, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithContextAndInvalidKey(): void
    {
        $data = [123 => 'value1'];
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->once())
            ->method('addError')
            ->with('expected string key, got integer');

        $result = $this->mapWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithContextAndInvalidValue(): void
    {
        $data = ['key1' => 'value1'];
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->once())
            ->method('pushPath')
            ->with('[key1]');

        $context->expects($this->once())
            ->method('popPath');

        $this->valueWriter->method('validate')
            ->with('value1', $context)
            ->willReturn(false);

        $result = $this->mapWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithContextAndMultipleErrors(): void
    {
        $data = [123 => 'value1', 'key2' => 'value2'];
        $context = $this->createMock(ValidationContextInterface::class);

        $context->expects($this->once())
            ->method('addError')
            ->with('expected string key, got integer');

        $context->expects($this->once())
            ->method('pushPath')
            ->with('[key2]');

        $context->expects($this->once())
            ->method('popPath');

        $this->valueWriter->method('validate')
            ->with('value2', $context)
            ->willReturn(false);

        $result = $this->mapWriter->validate($data, $context);

        $this->assertFalse($result);
    }

    public function testWriteWithBlockSizesAndEmptyBlock(): void
    {
        $mapWriter = new MapWriter($this->valueWriter, $this->encoder, 2, true);
        $data = ['key1' => 'value1'];

        $this->encoder->expects($this->exactly(3))
            ->method('writeLong');

        $this->encoder->expects($this->exactly(2))
            ->method('writeString');

        $this->valueWriter->expects($this->once())
            ->method('write');

        $mapWriter->write($data, $this->stream);
    }
}
