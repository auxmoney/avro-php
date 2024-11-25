<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOSchemaMatchException;
use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;

class AvroIODatumReader extends \Apache\Avro\Datum\AvroIODatumReader
{
    /**
     * @param array<string, LogicalTypeInterface> $logicalTypes
     * @param AvroSchema $writers_schema
     */
    public function __construct(
        private readonly array $logicalTypes = [],
        $writers_schema = null
    ) {
        parent::__construct($writers_schema);
    }

    /**
     * @throws AvroException
     * @throws AvroIOSchemaMatchException
     * @throws AvroSchemaParseException
     */
    public function readData($writers_schema, $readers_schema, $decoder)
    {
        $datum = parent::readData($writers_schema, $readers_schema, $decoder);

        $logicalType = $this->getLogicalType($writers_schema);
        return $logicalType !== null ? $logicalType->denormalize($writers_schema, $readers_schema, $datum) : $datum;
    }

    /**
     * @throws AvroSchemaParseException
     */
    protected function getLogicalType(AvroSchema $schema): ?LogicalTypeInterface
    {
        $logicalTypeKey = $schema->extraAttributes['logicalType'] ?? null;
        if ($logicalTypeKey === null) {
            return null;
        }

        return $this->logicalTypes[$logicalTypeKey] ?? throw new AvroSchemaParseException("Unknown logical type: $logicalTypeKey");
    }
}
