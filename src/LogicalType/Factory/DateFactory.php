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
        $type = $attributes['type'] ?? null;
        if ($type !== 'int' && $type !== 'long') {
            throw new InvalidSchemaException('The "date" logical type can only be used with an "int" or "long" type');
        }

        return new DateType();
    }
}
