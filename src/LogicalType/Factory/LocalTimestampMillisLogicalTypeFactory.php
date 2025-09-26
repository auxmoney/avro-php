<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType\Factory;

use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\LogicalType\LocalTimestampMillisLogicalType;

class LocalTimestampMillisLogicalTypeFactory implements LogicalTypeFactoryInterface
{
    public function getName(): string
    {
        return 'local-timestamp-millis';
    }

    public function create(array $attributes): LogicalTypeInterface
    {
        return new LocalTimestampMillisLogicalType();
    }
}