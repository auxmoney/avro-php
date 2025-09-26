<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\LocalTimestampMicrosType;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMicrosFactory;
use PHPUnit\Framework\TestCase;

class LocalTimestampMicrosFactoryTest extends TestCase
{
    private LocalTimestampMicrosFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LocalTimestampMicrosFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('local-timestamp-micros', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create([]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(LocalTimestampMicrosType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(LocalTimestampMicrosType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create([]);
        $result2 = $this->factory->create([]);

        $this->assertInstanceOf(LocalTimestampMicrosType::class, $result1);
        $this->assertInstanceOf(LocalTimestampMicrosType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}