<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\TimestampMillisType;
use Auxmoney\Avro\LogicalType\Factory\TimestampMillisFactory;
use PHPUnit\Framework\TestCase;

class TimestampMillisFactoryTest extends TestCase
{
    private TimestampMillisFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TimestampMillisFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('timestamp-millis', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimestampMillisType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimestampMillisType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(TimestampMillisType::class, $result1);
        $this->assertInstanceOf(TimestampMillisType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}