<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\TimeMicrosType;

class TimeMicrosFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'time-micros';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'long') {
            throw new InvalidSchemaException('The "time-micros" logical type can only be used with a "long" type');
        }

        return new TimeMicrosType();
    }
}
