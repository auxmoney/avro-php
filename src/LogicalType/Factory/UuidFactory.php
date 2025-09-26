<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\UuidType;

class UuidFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'uuid';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        return new UuidType();
    }
}
