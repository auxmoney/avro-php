<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\TimestampMicrosType;

class TimestampMicrosFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'timestamp-micros';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        return new TimestampMicrosType();
    }
}
