<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Contracts;

use Auxmoney\Avro\Exceptions\InvalidSchemaException;

interface LogicalTypeFactoryInterface
{
    public function getName(): string;

    /**
     * @param array<mixed> $attributes
     * @throws InvalidSchemaException
     */
    public function create(array $attributes): LogicalTypeInterface;
}
