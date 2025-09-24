<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\Factory\TimeMillisFactory;
use Auxmoney\Avro\LogicalType\TimeMillisType;
use PHPUnit\Framework\TestCase;

class TimeMillisFactoryTest extends TestCase
{
    private TimeMillisFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TimeMillisFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('time-millis', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimeMillisType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimeMillisType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(TimeMillisType::class, $result1);
        $this->assertInstanceOf(TimeMillisType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}
