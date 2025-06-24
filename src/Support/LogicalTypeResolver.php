<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;

class LogicalTypeResolver
{
    /** @var array<string, LogicalTypeFactoryInterface> */
    private array $logicalTypeFactories;

    /**
     * @param iterable<LogicalTypeFactoryInterface> $logicalTypeFactories
     */
    public function __construct(iterable $logicalTypeFactories)
    {
        $keyedLogicalTypeFactories = [];
        foreach ($logicalTypeFactories as $logicalTypeFactory) {
            $keyedLogicalTypeFactories[$logicalTypeFactory->getName()] = $logicalTypeFactory;
        }

        $this->logicalTypeFactories = $keyedLogicalTypeFactories;
    }

    public function resolve(string $name): ?LogicalTypeFactoryInterface
    {
        return $this->logicalTypeFactories[$name] ?? null;
    }
}
