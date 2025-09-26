<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\UuidType;
use Auxmoney\Avro\LogicalType\Factory\UuidFactory;
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
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(UuidType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(UuidType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(UuidType::class, $result1);
        $this->assertInstanceOf(UuidType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}