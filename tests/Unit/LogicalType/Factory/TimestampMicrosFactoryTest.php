<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\Factory\TimestampMicrosFactory;
use Auxmoney\Avro\LogicalType\TimestampMicrosType;
use PHPUnit\Framework\TestCase;

class TimestampMicrosFactoryTest extends TestCase
{
    private TimestampMicrosFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TimestampMicrosFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('timestamp-micros', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create(['type' => 'long']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimestampMicrosType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['type' => 'long', 'someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(TimestampMicrosType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create(['type' => 'long']);
        $result2 = $this->factory->create(['type' => 'long']);

        $this->assertInstanceOf(TimestampMicrosType::class, $result1);
        $this->assertInstanceOf(TimestampMicrosType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}
