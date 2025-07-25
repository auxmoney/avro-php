<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Deserialization;

use Auxmoney\Avro\Contracts\ReadableStreamInterface;
use Auxmoney\Avro\Contracts\ReaderInterface;
use Auxmoney\Avro\Deserialization\PropertyReader;
use PHPUnit\Framework\TestCase;

class PropertyReaderTest extends TestCase
{
    private PropertyReader $propertyReader;
    private ReaderInterface&\PHPUnit\Framework\MockObject\MockObject $typeReader;
    private ReadableStreamInterface&\PHPUnit\Framework\MockObject\MockObject $stream;

    protected function setUp(): void
    {
        $this->typeReader = $this->createMock(ReaderInterface::class);
        $this->stream = $this->createMock(ReadableStreamInterface::class);
        $this->propertyReader = new PropertyReader($this->typeReader, 'testProperty', false, null);
    }

    public function testReadWithNonNullValue(): void
    {
        $expectedValue = 'test value';
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testReadWithNullValueAndNoDefault(): void
    {
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn(null);

        $this->propertyReader->read($this->stream, $record);

        $this->assertNull($record['testProperty']);
    }

    public function testReadWithNullValueAndHasDefault(): void
    {
        $defaultValue = 'default value';
        $record = [];

        $propertyReaderWithDefault = new PropertyReader($this->typeReader, 'testProperty', true, $defaultValue);

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn(null);

        $propertyReaderWithDefault->read($this->stream, $record);

        $this->assertSame($defaultValue, $record['testProperty']);
    }

    public function testReadWithNonNullValueAndHasDefault(): void
    {
        $expectedValue = 'actual value';
        $defaultValue = 'default value';
        $record = [];

        $propertyReaderWithDefault = new PropertyReader($this->typeReader, 'testProperty', true, $defaultValue);

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $propertyReaderWithDefault->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testReadWithDifferentPropertyName(): void
    {
        $expectedValue = 'test value';
        $record = [];

        $propertyReader = new PropertyReader($this->typeReader, 'differentName', false, null);

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['differentName']);
    }

    public function testReadWithExistingRecordData(): void
    {
        $expectedValue = 'test value';
        $record = ['existingProperty' => 'existing value'];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame('existing value', $record['existingProperty']);
        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testSkip(): void
    {
        $this->typeReader->expects($this->once())
            ->method('skip')
            ->with($this->stream);

        $this->propertyReader->skip($this->stream);
    }

    public function testConstructorProperties(): void
    {
        $typeReader = $this->createMock(ReaderInterface::class);
        $name = 'testName';
        $hasDefault = true;
        $default = 'default value';

        $propertyReader = new PropertyReader($typeReader, $name, $hasDefault, $default);

        $this->assertSame($typeReader, $propertyReader->typeReader);
        $this->assertSame($name, $propertyReader->name);
        $this->assertSame($hasDefault, $propertyReader->hasDefault);
        $this->assertSame($default, $propertyReader->default);
    }

    public function testReadWithZeroValue(): void
    {
        $expectedValue = 0;
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testReadWithEmptyStringValue(): void
    {
        $expectedValue = '';
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testReadWithFalseValue(): void
    {
        $expectedValue = false;
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }

    public function testReadWithArrayValue(): void
    {
        $expectedValue = ['key' => 'value'];
        $record = [];

        $this->typeReader->expects($this->once())
            ->method('read')
            ->with($this->stream)
            ->willReturn($expectedValue);

        $this->propertyReader->read($this->stream, $record);

        $this->assertSame($expectedValue, $record['testProperty']);
    }
}
