<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\Factory\LocalTimestampMillisFactory;
use Auxmoney\Avro\LogicalType\LocalTimestampMillisType;
use PHPUnit\Framework\TestCase;

class LocalTimestampMillisFactoryTest extends TestCase
{
    private LocalTimestampMillisFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new LocalTimestampMillisFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('local-timestamp-millis', $this->factory->getName());
    }

    public function testCreateWithEmptyAttributes(): void
    {
        $result = $this->factory->create(['type' => 'long']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(LocalTimestampMillisType::class, $result);
    }

    public function testCreateWithAttributes(): void
    {
        $result = $this->factory->create(['type' => 'long', 'someAttribute' => 'value']);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(LocalTimestampMillisType::class, $result);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $result1 = $this->factory->create(['type' => 'long']);
        $result2 = $this->factory->create(['type' => 'long']);

        $this->assertInstanceOf(LocalTimestampMillisType::class, $result1);
        $this->assertInstanceOf(LocalTimestampMillisType::class, $result2);
        $this->assertNotSame($result1, $result2);
    }
}
