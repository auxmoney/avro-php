<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\Factory\UuidFactory;
use Auxmoney\Avro\LogicalType\UuidType;
use PHPUnit\Framework\TestCase;

class UuidFactoryTest extends TestCase
{
    private UuidFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('uuid', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([
            'type' => 'fixed',
            'size' => 16,
        ]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(UuidType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create([
            'type' => 'fixed',
            'size' => 16,
            'someAttribute' => 'value',
        ]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(UuidType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([
            'type' => 'fixed',
            'size' => 16,
        ]);
        $result2 = $this->factory->create([
            'type' => 'fixed',
            'size' => 16,
        ]);

        $this->assertInstanceOf(UuidType::class, $result1);
        $this->assertInstanceOf(UuidType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }

    public function testCreateWithWrongTypeThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('The "uuid" logical type can only be used with a "fixed" type');

        $this->factory->create([
            'type' => 'string',
            'size' => 16,
        ]);
    }

    public function testCreateWithWrongSizeThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('The "uuid" logical type must be used with a "fixed" type of size 16');

        $this->factory->create([
            'type' => 'fixed',
            'size' => 8,
        ]);
    }

    public function testCreateWithoutTypeThrowsException(): void
    {
        $this->expectException(InvalidSchemaException::class);
        $this->expectExceptionMessage('The "uuid" logical type can only be used with a "fixed" type');

        $this->factory->create(['size' => 16]);
    }
}
