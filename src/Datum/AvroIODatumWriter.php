<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOTypeException;
use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;

class AvroIODatumWriter extends \Apache\Avro\Datum\AvroIODatumWriter
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
     * @throws AvroIOTypeException
     * @throws AvroSchemaParseException
     */
    public function writeData($writers_schema, $datum, $encoder): void
    {
        $logicalType = $this->getLogicalType($writers_schema);
        if ($logicalType !== null) {
            if (!$logicalType->isValid($writers_schema, $datum)) {
                throw new AvroException(
                    sprintf(
                        'The datum %s is not an example of the logical type %s',
                        var_export($datum, true),
                        $logicalType::class
                    )
                );
            }

            $logicalType->writeData($writers_schema, $datum, $encoder);
            return;
        }

        parent::writeData($writers_schema, $datum, $encoder);
    }

    protected function isValidDatum(AvroSchema $schema, mixed $datum): bool
    {
        $logicalType = $this->getLogicalType($schema);
        if ($logicalType !== null) {
            return $logicalType->isValid($schema, $datum);
        }

        switch ($schema->type) {
            case AvroSchema::NULL_TYPE:
                return is_null($datum);
            case AvroSchema::BOOLEAN_TYPE:
                return is_bool($datum);
            case AvroSchema::STRING_TYPE:
            case AvroSchema::BYTES_TYPE:
                return is_string($datum);
            case AvroSchema::INT_TYPE:
                return (is_int($datum)
                    && (AvroSchema::INT_MIN_VALUE <= $datum)
                    && ($datum <= AvroSchema::INT_MAX_VALUE));
            case AvroSchema::LONG_TYPE:
                return (is_int($datum)
                    && (AvroSchema::LONG_MIN_VALUE <= $datum)
                    && ($datum <= AvroSchema::LONG_MAX_VALUE));
            case AvroSchema::FLOAT_TYPE:
            case AvroSchema::DOUBLE_TYPE:
                return (is_float($datum) || is_int($datum));
            case AvroSchema::ARRAY_SCHEMA:
                if (is_array($datum)) {
                    foreach ($datum as $d) {
                        if (!$this->isValidDatum($schema->items(), $d)) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            case AvroSchema::MAP_SCHEMA:
                if (is_array($datum)) {
                    foreach ($datum as $k => $v) {
                        if (
                            !is_string($k)
                            || !$this->isValidDatum($schema->values(), $v)
                        ) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            case AvroSchema::UNION_SCHEMA:
                foreach ($schema->schemas() as $schema) {
                    if ($this->isValidDatum($schema, $datum)) {
                        return true;
                    }
                }
                return false;
            case AvroSchema::ENUM_SCHEMA:
                return in_array($datum, $schema->symbols());
            case AvroSchema::FIXED_SCHEMA:
                return (is_string($datum)
                    && (strlen($datum) == $schema->size()));
            case AvroSchema::RECORD_SCHEMA:
            case AvroSchema::ERROR_SCHEMA:
            case AvroSchema::REQUEST_SCHEMA:
                if (is_array($datum)) {
                    foreach ($schema->fields() as $field) {
                        if (!$this->isValidDatum($field->type(), $datum[$field->name()] ?? null)) {
                            return false;
                        }
                    }
                    return true;
                } elseif (is_object($datum)) {
                    foreach ($schema->fields() as $field) {
                        if (!$this->isValidDatum($field->type(), $this->getFieldValue($datum, $field->name()))) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            default:
                throw new AvroSchemaParseException(sprintf('%s is not allowed.', $schema));
        }
    }

    protected function writeRecord($writers_schema, $datum, $encoder)
    {
        if (is_array($datum)) {
            foreach ($writers_schema->fields() as $field) {
                $this->writeData($field->type(), $datum[$field->name()] ?? null, $encoder);
            }
        } elseif (is_object($datum)) {
            foreach ($writers_schema->fields() as $field) {
                $this->writeData($field->type(), $this->getFieldValue($datum, $field->name()), $encoder);
            }
        }
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

    private function getFieldValue(object $datum, string $fieldName): mixed
    {
        if (isset($datum->{$fieldName})) {
            return $datum->{$fieldName};
        }

        /** @infection-ignore-all */
        $ucfirst = ucfirst($fieldName);

        $getter = 'get' . $ucfirst;
        if (method_exists($datum, $getter)) {
            return $datum->{$getter}();
        }

        $isser = 'is' . $ucfirst;
        if (method_exists($datum, $isser)) {
            return $datum->{$isser}();
        }

        $hasser = 'has' . $ucfirst;
        if (method_exists($datum, $hasser)) {
            return $datum->{$hasser}();
        }

        return null;
    }
}
