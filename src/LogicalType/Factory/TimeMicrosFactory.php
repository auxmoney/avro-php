<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\TimeMicrosType;

class TimeMicrosFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'time-micros';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        return new TimeMicrosType();
    }
}
