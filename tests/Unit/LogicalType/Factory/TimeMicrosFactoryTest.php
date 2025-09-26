<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\TimeMicrosType;
use Auxmoney\Avro\LogicalType\Factory\TimeMicrosFactory;
use PHPUnit\Framework\TestCase;

class TimeMicrosFactoryTest extends TestCase
{
    private TimeMicrosFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TimeMicrosFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('time-micros', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimeMicrosType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimeMicrosType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(TimeMicrosType::class, $result1);
        $this->assertInstanceOf(TimeMicrosType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}