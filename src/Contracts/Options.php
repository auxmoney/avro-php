<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

readonly class Options
{
    /**
     * @param iterable<LogicalTypeFactoryInterface> $logicalTypeFactories
     */
    public function __construct(
        public iterable $logicalTypeFactories = [],
        public bool $arrayWriteBlockSize = false,
        public int $arrayBlockCount = 0,
        public bool $mapWriteBlockSize = false,
        public int $mapBlockCount = 0,
    ) {
    }
}
