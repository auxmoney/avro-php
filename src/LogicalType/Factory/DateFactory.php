<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\DateType;

class DateFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'date';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'int') {
            throw new InvalidSchemaException('The "date" logical type can only be used with an "int" type');
        }

        return new DateType();
    }
}
