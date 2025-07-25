<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\DoubleWriter;
use PHPUnit\Framework\TestCase;
use stdClass;

class DoubleWriterTest extends TestCase
{
    private DoubleWriter $doubleWriter;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ValidationContextInterface&\PHPUnit\Framework\MockObject\MockObject $context;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->context = $this->createMock(ValidationContextInterface::class);
        $this->doubleWriter = new DoubleWriter($this->encoder);
    }

    public function testWriteDelegatesToEncoder(): void
    {
        $datum = 42;
        $encodedData = 'encoded_data';

        $this->encoder->expects($this->once())
            ->method('encodeDouble')
            ->with($datum)
            ->willReturn($encodedData);

        $this->stream->expects($this->once())
            ->method('write')
            ->with($encodedData);

        $this->doubleWriter->write($datum, $this->stream);
    }

    public function testValidateWithInteger(): void
    {
        $result = $this->doubleWriter->validate(42);

        $this->assertTrue($result);
    }

    public function testValidateWithFloat(): void
    {
        $result = $this->doubleWriter->validate(3.14159);

        $this->assertTrue($result);
    }

    public function testValidateWithZeroInteger(): void
    {
        $result = $this->doubleWriter->validate(0);

        $this->assertTrue($result);
    }

    public function testValidateWithZeroFloat(): void
    {
        $result = $this->doubleWriter->validate(0.0);

        $this->assertTrue($result);
    }

    public function testValidateWithNegativeInteger(): void
    {
        $result = $this->doubleWriter->validate(-42);

        $this->assertTrue($result);
    }

    public function testValidateWithNegativeFloat(): void
    {
        $result = $this->doubleWriter->validate(-3.14159);

        $this->assertTrue($result);
    }

    public function testValidateWithString(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected int or float, got string');

        $result = $this->doubleWriter->validate('42', $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithArray(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected int or float, got array');

        $result = $this->doubleWriter->validate([42], $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithObject(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected int or float, got object');

        $result = $this->doubleWriter->validate(new stdClass(), $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithNull(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected int or float, got NULL');

        $result = $this->doubleWriter->validate(null, $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithBoolean(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected int or float, got boolean');

        $result = $this->doubleWriter->validate(true, $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidTypeWithoutContext(): void
    {
        $result = $this->doubleWriter->validate('invalid');

        $this->assertFalse($result);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->doubleWriter->validate('invalid', null);

        $this->assertFalse($result);
    }
}
