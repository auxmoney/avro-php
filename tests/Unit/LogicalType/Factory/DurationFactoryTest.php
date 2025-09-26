<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\DurationType;
use Auxmoney\Avro\LogicalType\Factory\DurationFactory;
use PHPUnit\Framework\TestCase;

class DurationFactoryTest extends TestCase
{
    private DurationFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DurationFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('duration', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(DurationType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(DurationType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(DurationType::class, $result1);
        $this->assertInstanceOf(DurationType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}