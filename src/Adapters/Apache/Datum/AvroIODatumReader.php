<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Adapters\Apache\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOSchemaMatchException;
use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;

class AvroIODatumReader extends \Apache\Avro\Datum\AvroIODatumReader
{
    /**
     * @param array<string, LogicalTypeFactoryInterface> $logicalTypes
     */
    public function __construct(
        private readonly array $logicalTypes = []
    ) {
        parent::__construct();
    }

    /**
     * @param AvroSchema $writers_schema
     * @param AvroSchema $readers_schema
     * @param \Apache\Avro\Datum\AvroIOBinaryDecoder $decoder
     * @throws AvroException
     * @throws AvroIOSchemaMatchException
     * @throws AvroSchemaParseException
     */
    public function readData($writers_schema, $readers_schema, $decoder): mixed
    {
        $datum = parent::readData($writers_schema, $readers_schema, $decoder);

        $logicalType = $this->getLogicalType($writers_schema, $readers_schema);

        return $logicalType !== null ? $logicalType->denormalize($datum) : $datum;
    }

    /**
     * @throws AvroSchemaParseException
     */
    private function getLogicalType(AvroSchema $writersSchema, AvroSchema $readersSchema): ?LogicalTypeInterface
    {
        $writersLogicalTypeKey = $writersSchema->extraAttributes['logicalType'] ?? null;
        if ($writersLogicalTypeKey === null) {
            return null;
        }

        $readersLogicalTypeKey = $readersSchema->extraAttributes['logicalType'] ?? null;
        if ($readersLogicalTypeKey === null) {
            return null;
        }

        if (!is_string($writersLogicalTypeKey) || !is_string($readersLogicalTypeKey)) {
            throw new AvroSchemaParseException('Logical type must be a string');
        }

        if ($writersLogicalTypeKey !== $readersLogicalTypeKey) {
            throw new AvroSchemaParseException(
                "Writers logical type: {$writersLogicalTypeKey} does not match readers logical type: {$readersLogicalTypeKey}"
            );
        }

        $logicalTypeFactory = $this->logicalTypes[$writersLogicalTypeKey] ?? null;
        return $logicalTypeFactory?->create($writersSchema->extraAttributes);
    }
}
