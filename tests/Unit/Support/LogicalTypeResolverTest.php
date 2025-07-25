<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Tests\Unit\Support;

use ArrayIterator;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Support\LogicalTypeResolver;
use PHPUnit\Framework\TestCase;

class LogicalTypeResolverTest extends TestCase
{
    public function testResolveReturnsFactoryWhenFound(): void
    {
        $factory = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory->method('getName')->willReturn('date');

        $resolver = new LogicalTypeResolver([$factory]);

        $result = $resolver->resolve('date');

        $this->assertSame($factory, $result);
    }

    public function testResolveReturnsNullWhenNotFound(): void
    {
        $factory = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory->method('getName')->willReturn('date');

        $resolver = new LogicalTypeResolver([$factory]);

        $result = $resolver->resolve('timestamp');

        $this->assertNull($result);
    }

    public function testResolveWithMultipleFactories(): void
    {
        $dateFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $dateFactory->method('getName')->willReturn('date');

        $timestampFactory = $this->createMock(LogicalTypeFactoryInterface::class);
        $timestampFactory->method('getName')->willReturn('timestamp');

        $resolver = new LogicalTypeResolver([$dateFactory, $timestampFactory]);

        $this->assertSame($dateFactory, $resolver->resolve('date'));
        $this->assertSame($timestampFactory, $resolver->resolve('timestamp'));
        $this->assertNull($resolver->resolve('decimal'));
    }

    public function testResolveWithEmptyFactories(): void
    {
        $resolver = new LogicalTypeResolver([]);

        $result = $resolver->resolve('date');

        $this->assertNull($result);
    }

    public function testResolveWithDuplicateFactoryNames(): void
    {
        $factory1 = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory1->method('getName')->willReturn('date');

        $factory2 = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory2->method('getName')->willReturn('date');

        $resolver = new LogicalTypeResolver([$factory1, $factory2]);

        // Should return the last one registered
        $result = $resolver->resolve('date');

        $this->assertSame($factory2, $result);
    }

    public function testResolveWithIterator(): void
    {
        $factory = $this->createMock(LogicalTypeFactoryInterface::class);
        $factory->method('getName')->willReturn('date');

        $iterator = new ArrayIterator([$factory]);
        $resolver = new LogicalTypeResolver($iterator);

        $result = $resolver->resolve('date');

        $this->assertSame($factory, $result);
    }
}
