<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOBinaryDecoder;
use Apache\Avro\Datum\AvroIOBinaryEncoder;
use Apache\Avro\Datum\AvroIOTypeException;
use Apache\Avro\Schema\AvroSchema;

interface LogicalTypeInterface
{
    public function getName(): string;

    public function isValid(AvroSchema $schema, mixed $datum): bool;

    /**
     * @throws AvroIOTypeException if $datum is invalid for $writersSchema
     * @throws AvroException
     */
    public function normalize(AvroSchema $writersSchema, mixed $datum): mixed;

    public function denormalize(AvroSchema $writersSchema, AvroSchema $readersSchema, mixed $datum): mixed;
}
