<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\DateType;

class DateFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'date';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        return new DateType();
    }
}
