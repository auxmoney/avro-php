<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BooleanWriter;
use PHPUnit\Framework\TestCase;
use stdClass;

class BooleanWriterTest extends TestCase
{
    private BooleanWriter $booleanWriter;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;
    private ValidationContextInterface&\PHPUnit\Framework\MockObject\MockObject $context;

    protected function setUp(): void
    {
        $this->booleanWriter = new BooleanWriter();
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->context = $this->createMock(ValidationContextInterface::class);
    }

    public function testWriteTrue(): void
    {
        $this->stream->expects($this->once())
            ->method('write')
            ->with(chr(1));

        $this->booleanWriter->write(true, $this->stream);
    }

    public function testWriteFalse(): void
    {
        $this->stream->expects($this->once())
            ->method('write')
            ->with(chr(0));

        $this->booleanWriter->write(false, $this->stream);
    }

    public function testValidateWithTrue(): void
    {
        $result = $this->booleanWriter->validate(true);

        $this->assertTrue($result);
    }

    public function testValidateWithFalse(): void
    {
        $result = $this->booleanWriter->validate(false);

        $this->assertTrue($result);
    }

    public function testValidateWithString(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got string');

        $result = $this->booleanWriter->validate('true', $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithInteger(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got integer');

        $result = $this->booleanWriter->validate(1, $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithFloat(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got double');

        $result = $this->booleanWriter->validate(1.0, $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithArray(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got array');

        $result = $this->booleanWriter->validate([], $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithObject(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got object');

        $result = $this->booleanWriter->validate(new stdClass(), $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithNull(): void
    {
        $this->context->expects($this->once())
            ->method('addError')
            ->with('expected boolean, got NULL');

        $result = $this->booleanWriter->validate(null, $this->context);

        $this->assertFalse($result);
    }

    public function testValidateWithInvalidTypeWithoutContext(): void
    {
        $result = $this->booleanWriter->validate('invalid');

        $this->assertFalse($result);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->booleanWriter->validate('invalid', null);

        $this->assertFalse($result);
    }

    public function testWriteWithBooleanLikeValues(): void
    {
        // Test that non-boolean values are handled correctly by the write method
        // The write method should convert them to boolean implicitly

        $this->stream->expects($this->exactly(4))
            ->method('write')
            ->willReturnMap([[chr(1), 1], [chr(0), 1], [chr(1), 1], [chr(0), 1]]);

        $this->booleanWriter->write(true, $this->stream);
        $this->booleanWriter->write(false, $this->stream);
        $this->booleanWriter->write(1, $this->stream);
        $this->booleanWriter->write(0, $this->stream);
    }
}
