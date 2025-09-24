<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\UuidType;

class UuidFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'uuid';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'fixed') {
            throw new InvalidSchemaException('The "uuid" logical type can only be used with a "fixed" type');
        }

        if (($attributes['size'] ?? null) !== 16) {
            throw new InvalidSchemaException('The "uuid" logical type must be used with a "fixed" type of size 16');
        }

        return new UuidType();
    }
}
