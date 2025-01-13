<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Adapters\Apache\Datum;

use Apache\Avro\AvroException;
use Apache\Avro\Datum\AvroIOTypeException;
use Apache\Avro\Schema\AvroArraySchema;
use Apache\Avro\Schema\AvroEnumSchema;
use Apache\Avro\Schema\AvroField;
use Apache\Avro\Schema\AvroFixedSchema;
use Apache\Avro\Schema\AvroMapSchema;
use Apache\Avro\Schema\AvroRecordSchema;
use Apache\Avro\Schema\AvroSchema;
use Apache\Avro\Schema\AvroSchemaParseException;
use Apache\Avro\Schema\AvroUnionSchema;
use Auxmoney\Avro\Contracts\LogicalTypeFactoryInterface;
use Auxmoney\Avro\Contracts\LogicalTypeInterface;

class AvroIODatumWriter extends \Apache\Avro\Datum\AvroIODatumWriter
{
    /**
     * @param array<string, LogicalTypeFactoryInterface> $logicalTypesFactories
     */
    public function __construct(
        private readonly array $logicalTypesFactories = [],
    ) {
        parent::__construct();
    }

    /**
     * @param mixed $datum
     * @throws AvroException
     * @throws AvroIOTypeException
     * @throws AvroSchemaParseException
     */
    protected function writeValidatedData($writers_schema, $datum, $encoder): void
    {
        $logicalType = $this->getLogicalType($writers_schema);
        $normalized = $logicalType !== null ? $logicalType->normalize($datum) : $datum;

        parent::writeValidatedData($writers_schema, $normalized, $encoder);
    }

    protected function isValidDatum(AvroSchema $schema, mixed $datum): bool
    {
        $logicalType = $this->getLogicalType($schema);
        if ($logicalType !== null) {
            return $logicalType->isValid($datum);
        }

        switch ($schema->type) {
            case AvroSchema::NULL_TYPE:
                return $datum === null;
            case AvroSchema::BOOLEAN_TYPE:
                return is_bool($datum);
            case AvroSchema::STRING_TYPE:
            case AvroSchema::BYTES_TYPE:
                return is_string($datum);
            case AvroSchema::INT_TYPE:
                return is_int($datum)
                    && ($datum >= AvroSchema::INT_MIN_VALUE)
                    && ($datum <= AvroSchema::INT_MAX_VALUE);
            case AvroSchema::LONG_TYPE:
                return is_int($datum)
                    && ($datum >= AvroSchema::LONG_MIN_VALUE)
                    && ($datum <= AvroSchema::LONG_MAX_VALUE);
            case AvroSchema::FLOAT_TYPE:
            case AvroSchema::DOUBLE_TYPE:
                return is_float($datum) || is_int($datum);
            case AvroSchema::ARRAY_SCHEMA:
                if (is_array($datum)) {
                    assert($schema instanceof AvroArraySchema);
                    $items = $schema->items();
                    assert($items instanceof AvroSchema);
                    foreach ($datum as $d) {
                        if (!$this->isValidDatum($items, $d)) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            case AvroSchema::MAP_SCHEMA:
                if (is_array($datum)) {
                    assert($schema instanceof AvroMapSchema);
                    $values = $schema->values();
                    assert($values instanceof AvroSchema);
                    foreach ($datum as $k => $v) {
                        if (
                            !is_string($k)
                            || !$this->isValidDatum($values, $v)
                        ) {
                            return false;
                        }
                    }
                    return true;
                }
                return false;
            case AvroSchema::UNION_SCHEMA:
                assert($schema instanceof AvroUnionSchema);
                $schemas = $schema->schemas();
                foreach ($schemas as $schema) {
                    if ($this->isValidDatum($schema, $datum)) {
                        return true;
                    }
                }
                return false;
            case AvroSchema::ENUM_SCHEMA:
                assert($schema instanceof AvroEnumSchema);
                return in_array($datum, $schema->symbols());
            case AvroSchema::FIXED_SCHEMA:
                assert($schema instanceof AvroFixedSchema);
                return is_string($datum)
                    && (strlen($datum) == $schema->size());
            case AvroSchema::RECORD_SCHEMA:
            case AvroSchema::ERROR_SCHEMA:
            case AvroSchema::REQUEST_SCHEMA:
                assert($schema instanceof AvroRecordSchema);
                if (is_array($datum)) {
                    foreach ($schema->fields() as $field) {
                        assert($field instanceof AvroField);

                        /** @var AvroSchema $type */
                        $type = $field->type();
                        if (!$this->isValidDatum($type, $datum[$field->name()] ?? $field->defaultValue())) {
                            return false;
                        }
                    }
                    return true;
                } elseif (is_object($datum)) {
                    foreach ($schema->fields() as $field) {
                        assert($field instanceof AvroField);

                        /** @var AvroSchema $type */
                        $type = $field->type();
                        if (!$this->isValidDatum($type, $this->getFieldValue($datum, $field->name(), $field->defaultValue()))) {
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

    /**
     * @param AvroRecordSchema $writers_schema
     * @param mixed $datum
     * @param \Apache\Avro\Datum\AvroIOBinaryEncoder $encoder
     * @throws AvroException
     * @throws AvroIOTypeException
     * @throws AvroSchemaParseException
     */
    protected function writeRecord($writers_schema, $datum, $encoder): void
    {
        if (is_array($datum)) {
            foreach ($writers_schema->fields() as $field) {
                assert($field instanceof AvroField);
                /** @var AvroSchema $type */
                $type = $field->type();
                $this->writeValidatedData($type, $datum[$field->name()] ?? $field->defaultValue(), $encoder);
            }
        } elseif (is_object($datum)) {
            foreach ($writers_schema->fields() as $field) {
                assert($field instanceof AvroField);
                /** @var AvroSchema $type */
                $type = $field->type();

                $this->writeValidatedData($type, $this->getFieldValue($datum, $field->name(), $field->defaultValue()), $encoder);
            }
        }
    }

    /**
     * @throws AvroSchemaParseException
     */
    private function getLogicalType(AvroSchema $schema): ?LogicalTypeInterface
    {
        $logicalTypeKey = $schema->extraAttributes['logicalType'] ?? null;
        if ($logicalTypeKey === null) {
            return null;
        }

        $factory = $this->logicalTypesFactories[$logicalTypeKey] ?? null;
        return $factory?->create($schema->extraAttributes);
    }

    private function getFieldValue(object $datum, string $fieldName, mixed $defaultValue): mixed
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

        return $defaultValue;
    }
}
