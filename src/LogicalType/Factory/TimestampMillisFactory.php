<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\TimestampMillisType;

class TimestampMillisFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'timestamp-millis';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'long') {
            throw new InvalidSchemaException('The "timestamp-millis" logical type can only be used with a "long" type');
        }

        return new TimestampMillisType();
    }
}
