<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

interface LogicalTypeFactoryInterface
{
    public function getName(): string;

    public function create(array $attributes): LogicalTypeInterface;
}
