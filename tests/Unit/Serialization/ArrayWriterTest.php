<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Serialization;

use ArrayIterator;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Serialization\ArrayWriter;
use Auxmoney\Avro\Serialization\BinaryEncoder;
use Auxmoney\Avro\Serialization\ValidationContext;
use Countable;
use Generator;
use IteratorAggregate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArrayWriterTest extends TestCase
{
    private ArrayWriter $writer;
    private WriterInterface&\PHPUnit\Framework\MockObject\MockObject $itemWriter;
    private BinaryEncoder&\PHPUnit\Framework\MockObject\MockObject $encoder;
    private WritableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->itemWriter = $this->createMock(WriterInterface::class);
        $this->encoder = $this->createMock(BinaryEncoder::class);
        $this->stream = $this->createMock(WritableStreamInterface::class);

        // Default configuration without block writing
        $this->writer = new ArrayWriter($this->itemWriter, $this->encoder, blockCount: 0, writeBlockSize: false);
    }

    public function testValidateWithValidArray(): void
    {
        $context = new ValidationContext();
        $data = ['item1', 'item2', 'item3'];

        $this->itemWriter->expects($this->exactly(3))
            ->method('validate')
            ->willReturn(true);

        $result = $this->writer->validate($data, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithEmptyArray(): void
    {
        $context = new ValidationContext();
        $data = [];

        $this->itemWriter->expects($this->never())
            ->method('validate');

        $result = $this->writer->validate($data, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithCountableTraversable(): void
    {
        $context = new ValidationContext();
        $data = new ArrayIterator(['item1', 'item2']);

        $this->itemWriter->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(true);

        $result = $this->writer->validate($data, $context);

        $this->assertTrue($result);
        $this->assertEmpty($context->getContextErrors());
    }

    public function testValidateWithGenerator(): void
    {
        $context = new ValidationContext();
        $generator = (function () {
            yield 'item1';
            yield 'item2';
        })();

        $result = $this->writer->validate($generator, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('generators cannot be used as array values', $errors[0]);
    }

    public function testValidateWithNonIterable(): void
    {
        $context = new ValidationContext();
        $data = 'not an array';

        $result = $this->writer->validate($data, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('expected iterable, got string', $errors[0]);
    }

    public function testValidateWithNonIterableObject(): void
    {
        $context = new ValidationContext();
        $nonIterableObject = new stdClass();

        $this->itemWriter->expects($this->never())
            ->method('validate');

        $result = $this->writer->validate($nonIterableObject, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('expected iterable, got object', $errors[0]);
    }

    public function testValidateWithInvalidItems(): void
    {
        $context = new ValidationContext();
        $data = ['valid', 'invalid', 'also_valid'];

        $this->itemWriter->expects($this->exactly(3))
            ->method('validate')
            ->willReturnOnConsecutiveCalls(true, false, true);

        $result = $this->writer->validate($data, $context);

        $this->assertFalse($result);
    }

    public function testValidateWithPathTracking(): void
    {
        $context = new ValidationContext();
        $data = ['item1', 'item2'];

        $this->itemWriter->expects($this->exactly(2))
            ->method('validate')
            ->willReturnCallback(function ($item, $ctx) {
                $this->assertInstanceOf(ValidationContext::class, $ctx);
                return true;
            });

        $result = $this->writer->validate($data, $context);

        $this->assertTrue($result);
    }

    public function testValidateWithoutContext(): void
    {
        $data = ['item1', 'item2'];

        $this->itemWriter->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(true);

        $result = $this->writer->validate($data);

        $this->assertTrue($result);
    }

    public function testValidateWithoutContextShortCircuits(): void
    {
        // When no context is provided, validation should short-circuit on first failure
        $data = ['valid', 'invalid', 'also_valid'];

        $this->itemWriter->expects($this->exactly(2)) // Only first 2 items should be validated
            ->method('validate')
            ->willReturnOnConsecutiveCalls(true, false); // First succeeds, second fails

        $result = $this->writer->validate($data); // No context provided

        $this->assertFalse($result);
    }

    public function testWriteWithEmptyArray(): void
    {
        $data = [];

        $this->encoder->expects($this->once())
            ->method('writeLong')
            ->with($this->stream, 0); // Array terminator

        $this->writer->write($data, $this->stream);
    }

    public function testWriteWithNonEmptyArrayWithoutBlockSize(): void
    {
        $data = ['item1', 'item2'];

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 2, null], // Block count
                [$this->stream, 0, null], // Array terminator
            ]);

        $this->itemWriter->expects($this->exactly(2))
            ->method('write')
            ->willReturnMap([['item1', $this->stream, null], ['item2', $this->stream, null]]);

        $this->writer->write($data, $this->stream);
    }

    public function testWriteWithBlockSizes(): void
    {
        $writerWithBlockSize = new ArrayWriter($this->itemWriter, $this->encoder, blockCount: 0, writeBlockSize: true);

        $data = ['item1', 'item2'];

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, -2, null], // Negative block count
                [$this->stream, 0, null], // Array terminator
            ]);

        $this->encoder->expects($this->once())
            ->method('writeString')
            ->with($this->stream, $this->isType('string'));

        $this->itemWriter->expects($this->exactly(2))
            ->method('write');

        $writerWithBlockSize->write($data, $this->stream);
    }

    public function testWriteWithBlockCount(): void
    {
        $writerWithBlocks = new ArrayWriter($this->itemWriter, $this->encoder, blockCount: 2, writeBlockSize: false);

        $data = ['item1', 'item2', 'item3'];

        $this->encoder->expects($this->exactly(3))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 2, null], // First block
                [$this->stream, 1, null], // Second block
                [$this->stream, 0, null], // Array terminator
            ]);

        $this->itemWriter->expects($this->exactly(3))
            ->method('write');

        $writerWithBlocks->write($data, $this->stream);
    }

    public function testWriteWithCountableTraversable(): void
    {
        $data = new ArrayIterator(['item1', 'item2']);

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 2, null], // Block count
                [$this->stream, 0, null], // Array terminator
            ]);

        $this->itemWriter->expects($this->exactly(2))
            ->method('write');

        $this->writer->write($data, $this->stream);
    }

    public function testWriteWithCustomCountableTraversable(): void
    {
        $customTraversable = new class() implements IteratorAggregate, Countable {
            /** @var array<string> */
            private array $data = ['a', 'b', 'c'];

            public function getIterator(): Generator
            {
                foreach ($this->data as $item) {
                    yield $item;
                }
            }

            public function count(): int
            {
                return count($this->data);
            }
        };

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 3, null], // Block count
                [$this->stream, 0, null], // Array terminator
            ]);

        $this->itemWriter->expects($this->exactly(3))
            ->method('write');

        $this->writer->write($customTraversable, $this->stream);
    }

    public function testValidateWithMixedValidAndInvalidItems(): void
    {
        $context = new ValidationContext();
        $data = ['valid1', 'invalid', 'valid2', 'also_invalid'];

        $this->itemWriter->expects($this->exactly(4))
            ->method('validate')
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $result = $this->writer->validate($data, $context);

        $this->assertFalse($result);
    }

    #[DataProvider('invalidDataProvider')]
    public function testValidateWithInvalidDataTypes(mixed $invalidData, string $expectedErrorPattern): void
    {
        $context = new ValidationContext();

        $result = $this->writer->validate($invalidData, $context);

        $this->assertFalse($result);
        $errors = $context->getContextErrors();
        $this->assertNotEmpty($errors);
        $this->assertMatchesRegularExpression($expectedErrorPattern, $errors[0]);
    }

    public function testValidateWithNullContext(): void
    {
        $data = ['item1', 'item2'];

        $this->itemWriter->expects($this->exactly(2))
            ->method('validate')
            ->willReturn(true);

        $result = $this->writer->validate($data, null);

        $this->assertTrue($result);
    }

    public function testGetBlocksGeneratorWithZeroBlockCount(): void
    {
        // This tests the private getBlocksGenerator method indirectly
        $data = ['item1', 'item2', 'item3'];

        $this->encoder->expects($this->exactly(2))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 3, null], // All items in one block
                [$this->stream, 0, null], // Terminator
            ]);

        $this->itemWriter->expects($this->exactly(3))
            ->method('write');

        $this->writer->write($data, $this->stream);
    }

    public function testGetBlocksGeneratorWithSpecificBlockCount(): void
    {
        $writerWithBlocks = new ArrayWriter($this->itemWriter, $this->encoder, blockCount: 2, writeBlockSize: false);

        $data = ['item1', 'item2', 'item3', 'item4', 'item5'];

        // Should create 3 blocks: [item1, item2], [item3, item4], [item5]
        $this->encoder->expects($this->exactly(4))
            ->method('writeLong')
            ->willReturnMap([
                [$this->stream, 2, null], // First block
                [$this->stream, 2, null], // Second block
                [$this->stream, 1, null], // Third block
                [$this->stream, 0, null], // Terminator
            ]);

        $this->itemWriter->expects($this->exactly(5))
            ->method('write');

        $writerWithBlocks->write($data, $this->stream);
    }

    public static function invalidDataProvider(): Generator
    {
        yield 'string' => ['not_an_array', '/expected iterable, got string/'];
        yield 'integer' => [123, '/expected iterable, got integer/'];
        yield 'object' => [new stdClass(), '/expected iterable, got object/'];
        yield 'boolean' => [true, '/expected iterable, got boolean/'];
        yield 'null' => [null, '/expected iterable, got NULL/'];
        yield 'resource' => [fopen('php://memory', 'r'), '/expected iterable, got resource/'];
    }
}
