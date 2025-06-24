<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

readonly class ReaderOptions
{
    /**
     * @param iterable<LogicalTypeFactoryInterface> $logicalTypeFactories
     */
    public function __construct(
        public iterable $logicalTypeFactories = [],
    ) {
    }
}
