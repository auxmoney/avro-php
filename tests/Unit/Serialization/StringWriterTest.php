<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\StringWriter;
use Auxmoney\Avro\Serialization\ValidationContext;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class StringWriterTest extends TestCase
{
    private StringWriter $writer;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);
        $this->writer = new StringWriter($this->encoder);
    }

    public function testValidateWithValidString(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate('valid string', $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithEmptyString(): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate('', $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    #[DataProvider('invalidDataProvider')]
    public function testValidateWithInvalidData(mixed $invalidData, string $expectedType): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate($invalidData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("expected string, got {$expectedType}", $errors[0]);
    }

    public function testValidateWithoutContext(): void
    {
        $result = $this->writer->validate('valid string');

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithoutContext(): void
    {
        $result = $this->writer->validate(123);

        $this->assertFalse($result);
    }

    public function testWriteWithValidString(): void
    {
        $testString = 'test string';

        $this->encoder->expects($this->once())
            ->method('writeString')
            ->with($this->stream, $testString);

        $this->writer->write($testString, $this->stream);
    }

    public function testWriteWithEmptyString(): void
    {
        $emptyString = '';

        $this->encoder->expects($this->once())
            ->method('writeString')
            ->with($this->stream, $emptyString);

        $this->writer->write($emptyString, $this->stream);
    }

    public function testWriteWithUnicodeString(): void
    {
        $unicodeString = '🚀 Hello 世界 🌍';

        $this->encoder->expects($this->once())
            ->method('writeString')
            ->with($this->stream, $unicodeString);

        $this->writer->write($unicodeString, $this->stream);
    }

    public function testWriteWithLongString(): void
    {
        $longString = str_repeat('a', 10000);

        $this->encoder->expects($this->once())
            ->method('writeString')
            ->with($this->stream, $longString);

        $this->writer->write($longString, $this->stream);
    }

    public function testValidateWithNullContext(): void
    {
        $result = $this->writer->validate('valid string', null);

        $this->assertTrue($result);
    }

    public function testValidateInvalidDataWithNullContext(): void
    {
        $result = $this->writer->validate(123, null);

        $this->assertFalse($result);
    }

    public static function invalidDataProvider(): Generator
    {
        yield 'integer' => [123, 'integer'];
        yield 'float' => [12.34, 'double'];
        yield 'boolean_true' => [true, 'boolean'];
        yield 'boolean_false' => [false, 'boolean'];
        yield 'null' => [null, 'NULL'];
        yield 'array' => [[], 'array'];
        yield 'object' => [new stdClass(), 'object'];
        yield 'resource' => [fopen('php://memory', 'r'), 'resource'];
    }
}
