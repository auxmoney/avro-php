<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidSchemaException;
use Auxmoney\Avro\LogicalType\DecimalType;

class DecimalFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'decimal';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (($attributes['type'] ?? null) !== 'bytes' && ($attributes['type'] ?? null) !== 'fixed') {
            throw new InvalidSchemaException('The "decimal" logical type can only be used with a "bytes" or "fixed" type');
        }

        if (!isset($attributes['precision']) || !is_int($attributes['precision'])) {
            throw new InvalidSchemaException('Decimal logical type requires "precision" attribute and it must be an integer');
        }

        $precision = $attributes['precision'];
        if ($precision <= 0) {
            throw new InvalidSchemaException('Decimal precision must be a positive integer');
        }

        $scale = 0;
        if (isset($attributes['scale'])) {
            if (!is_int($attributes['scale'])) {
                throw new InvalidSchemaException('Decimal "scale" attribute must be an integer');
            }
            $scale = $attributes['scale'];
        }
        if ($scale < 0 || $scale > $precision) {
            throw new InvalidSchemaException('Decimal scale must be between 0 and precision');
        }

        return new DecimalType($precision, $scale);
    }
}
