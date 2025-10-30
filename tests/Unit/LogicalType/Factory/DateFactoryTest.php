<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\DateType;
use Auxmoney\Avro\LogicalType\Factory\DateFactory;
use PHPUnit\Framework\TestCase;

class DateFactoryTest extends TestCase
{
    private DateFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DateFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('date', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create(['type' => 'int']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(DateType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['type' => 'int', 'someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(DateType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create(['type' => 'int']);
        $result2 = $this->factory->create(['type' => 'int']);

        $this->assertInstanceOf(DateType::class, $result1);
        $this->assertInstanceOf(DateType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testCreateWithWrongTypeThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('The "date" logical type can only be used with an "int" type');

        $this->factory->create(['type' => 'string']);
    }

    public function testCreateWithoutTypeThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('The "date" logical type can only be used with an "int" type');

        $this->factory->create([]);
    }
}
