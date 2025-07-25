<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Support;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\DataMismatchException;
use Auxmoney\Avro\Support\ValidatorWriter;
use PHPUnit\Framework\TestCase;

class ValidatorWriterTest extends TestCase
{
    private ValidatorWriter $validatorWriter;
    private WriterInterface&\PHPUnit\Framework\MockObject\MockObject $innerWriter;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->innerWriter = $this->createMock(WriterInterface::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->validatorWriter = new ValidatorWriter($this->innerWriter);
    }

    public function testWriteWithValidData(): void
    {
        $data = 'test data';

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, $this->isInstanceOf(\Auxmoney\Avro\Serialization\ValidationContext::class))
            ->willReturn(true);

        $this->innerWriter->expects($this->once())
            ->method('write')
            ->with($data, $this->stream);

        $this->validatorWriter->write($data, $this->stream);
    }

    public function testWriteWithInvalidData(): void
    {
        $data = 'invalid data';

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, $this->isInstanceOf(\Auxmoney\Avro\Serialization\ValidationContext::class))
            ->willReturn(false);

        $this->innerWriter->expects($this->never())
            ->method('write');

        $this->expectException(DataMismatchException::class);

        $this->validatorWriter->write($data, $this->stream);
    }

    public function testValidateWithValidData(): void
    {
        $data = 'test data';

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, null)
            ->willReturn(true);

        $result = $this->validatorWriter->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidData(): void
    {
        $data = 'invalid data';

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, null)
            ->willReturn(false);

        $result = $this->validatorWriter->validate($data);

        $this->assertFalse($result);
    }

    public function testValidateWithContext(): void
    {
        $data = 'test data';
        $context = $this->createMock(ValidationContextInterface::class);

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, $context)
            ->willReturn(true);

        $result = $this->validatorWriter->validate($data, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithContextAndInvalidData(): void
    {
        $data = 'invalid data';
        $context = $this->createMock(ValidationContextInterface::class);

        $this->innerWriter->expects($this->once())
            ->method('validate')
            ->with($data, $context)
            ->willReturn(false);

        $result = $this->validatorWriter->validate($data, $context);

        $this->assertFalse($result);
    }
}
