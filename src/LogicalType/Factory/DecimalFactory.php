<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Exceptions\InvalidArgumentException;
use Auxmoney\Avro\LogicalType\DecimalType;

class DecimalFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'decimal';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        if (!isset($attributes['precision'])) {
            throw new InvalidArgumentException('Decimal logical type requires "precision" attribute');
        }

        $precision = (int) $attributes['precision'];
        if ($precision <= 0) {
            throw new InvalidArgumentException('Decimal precision must be a positive integer');
        }

        $scale = isset($attributes['scale']) ? (int) $attributes['scale'] : 0;
        if ($scale < 0 || $scale > $precision) {
            throw new InvalidArgumentException('Decimal scale must be between 0 and precision');
        }

        return new DecimalType($precision, $scale);
    }
}
