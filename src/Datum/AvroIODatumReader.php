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

        $logicalType = $this->getLogicalType($writers_schema, $readers_schema);

        return $logicalType !== null ? $logicalType->denormalize($writers_schema, $readers_schema, $datum) : $datum;
    }

    /**
     * @throws AvroSchemaParseException
     */
    protected function getLogicalType(AvroSchema $writersSchema, AvroSchema $readersSchema): ?LogicalTypeInterface
    {
        $writersLogicalTypeKey = $writersSchema->extraAttributes['logicalType'] ?? null;
        if ($writersLogicalTypeKey === null) {
            return null;
        }

        $readersLogicalTypeKey = $readersSchema->extraAttributes['logicalType'] ?? null;
        if ($readersLogicalTypeKey === null) {
            return null;
        }

        if ($writersLogicalTypeKey !== $readersLogicalTypeKey) {
            throw new AvroSchemaParseException("Writers logical type: $writersLogicalTypeKey does not match readers logical type: $readersLogicalTypeKey");
        }

        return $this->logicalTypes[$writersLogicalTypeKey]
            ?? throw new AvroSchemaParseException("Unknown logical type: $writersLogicalTypeKey");
    }
}
