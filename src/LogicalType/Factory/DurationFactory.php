<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\DurationType;

class DurationFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'duration';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'fixed') {
            throw new InvalidSchemaException('The "duration" logical type can only be used with a "fixed" type');
        }

        if (($attributes['size'] ?? null) !== 12) {
            throw new InvalidSchemaException('The "duration" logical type must be used with a "fixed" type of size 12');
        }

        return new DurationType();
    }
}
