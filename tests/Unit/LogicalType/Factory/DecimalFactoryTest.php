<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\DecimalType;
use Auxmoney\Avro\LogicalType\Factory\DecimalFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalFactoryTest extends TestCase
{
    private DecimalFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DecimalFactory();
    }

    public function testGetName(): void
    {
        $this->assertSame('decimal', $this->factory->getName());
    }

    public function testCreateWithValidPrecision(): void
    {
        $result = $this->factory->create(['precision' => 10]);

        $this->assertInstanceOf(LogicalTypeInterface::class, $result);
        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(10, $result->getPrecision());
        $this->assertSame(0, $result->getScale()); // Default scale
    }

    public function testCreateWithValidPrecisionAndScale(): void
    {
        $result = $this->factory->create(['precision' => 10, 'scale' => 3]);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(10, $result->getPrecision());
        $this->assertSame(3, $result->getScale());
    }

    public function testCreateWithStringPrecision(): void
    {
        $result = $this->factory->create(['precision' => '10', 'scale' => '3']);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(10, $result->getPrecision());
        $this->assertSame(3, $result->getScale());
    }

    public function testCreateWithZeroScale(): void
    {
        $result = $this->factory->create(['precision' => 5, 'scale' => 0]);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(5, $result->getPrecision());
        $this->assertSame(0, $result->getScale());
    }

    public function testCreateWithMaxValidScale(): void
    {
        $result = $this->factory->create(['precision' => 10, 'scale' => 10]);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(10, $result->getPrecision());
        $this->assertSame(10, $result->getScale());
    }

    public function testCreateWithMissingPrecision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal logical type requires "precision" attribute');

        $this->factory->create([]);
    }

    public function testCreateWithMissingPrecisionButHasScale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal logical type requires "precision" attribute');

        $this->factory->create(['scale' => 3]);
    }

    public function testCreateWithZeroPrecision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal precision must be a positive integer');

        $this->factory->create(['precision' => 0]);
    }

    public function testCreateWithNegativePrecision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal precision must be a positive integer');

        $this->factory->create(['precision' => -5]);
    }

    public function testCreateWithNegativeScale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal scale must be between 0 and precision');

        $this->factory->create(['precision' => 10, 'scale' => -1]);
    }

    public function testCreateWithScaleGreaterThanPrecision(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Decimal scale must be between 0 and precision');

        $this->factory->create(['precision' => 5, 'scale' => 6]);
    }

    public function testCreateReturnsNewInstanceEachTime(): void
    {
        $attributes = ['precision' => 10, 'scale' => 2];
        
        $result1 = $this->factory->create($attributes);
        $result2 = $this->factory->create($attributes);

        $this->assertInstanceOf(DecimalType::class, $result1);
        $this->assertInstanceOf(DecimalType::class, $result2);
        $this->assertNotSame($result1, $result2);
        
        // But they should have the same configuration
        /** @var DecimalType $result1 */
        /** @var DecimalType $result2 */
        $this->assertSame($result1->getPrecision(), $result2->getPrecision());
        $this->assertSame($result1->getScale(), $result2->getScale());
    }

    public function testCreateWithExtraAttributes(): void
    {
        $result = $this->factory->create([
            'precision' => 8,
            'scale' => 2,
            'extraAttribute' => 'ignored',
            'anotherOne' => 123
        ]);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(8, $result->getPrecision());
        $this->assertSame(2, $result->getScale());
    }

    public function testCreateWithLargePrecision(): void
    {
        $result = $this->factory->create(['precision' => 1000, 'scale' => 500]);

        $this->assertInstanceOf(DecimalType::class, $result);
        
        /** @var DecimalType $result */
        $this->assertSame(1000, $result->getPrecision());
        $this->assertSame(500, $result->getScale());
    }
}