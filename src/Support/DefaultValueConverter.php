<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Support;

use Auxmoney\Avro\Contracts\ValidationContextInterface;

/**
 * Converts default values according to their schema types.
 *
 * @phpstan-import-type NormalizedSchema from SchemaHelper
 * @phpstan-import-type PrimitiveSchema from SchemaHelper
 * @phpstan-import-type UnionSchema from SchemaHelper
 * @phpstan-import-type RecordSchema from SchemaHelper
 * @phpstan-import-type ArraySchema from SchemaHelper
 * @phpstan-import-type EnumSchema from SchemaHelper
 * @phpstan-import-type MapSchema from SchemaHelper
 * @phpstan-import-type FixedSchema from SchemaHelper
 */
class DefaultValueConverter
{
    public function __construct(
        private readonly SchemaHelper $schemaHelper,
    ) {
    }

    /**
     * Converts a default value according to its schema type.
     *
     * @param mixed $defaultValue The raw default value from the schema
     * @param array<mixed>|string $schema The schema defining the type of the default value
     * @param mixed $convertedValue The converted value (output parameter)
     * @param ValidationContextInterface $context Validation context for error reporting
     * @return bool True if conversion succeeded, false otherwise
     */
    public function convertDefaultValue(
        mixed $defaultValue,
        array|string $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        $normalizedSchema = $this->schemaHelper->normalizeSchema($schema);

        $success = match ($normalizedSchema['type']) {
            'null' => $this->convertNullDefaultValue($defaultValue, $convertedValue, $context),
            'boolean' => $this->convertBooleanDefaultValue($defaultValue, $convertedValue, $context),
            'int', 'long' => $this->convertIntegerDefaultValue($defaultValue, $convertedValue, $context),
            'float' => $this->convertFloatDefaultValue($defaultValue, $convertedValue, $context),
            'double' => $this->convertDoubleDefaultValue($defaultValue, $convertedValue, $context),
            'string' => $this->convertStringDefaultValue($defaultValue, $convertedValue, $context),
            'bytes' => $this->convertBytesDefaultValue($defaultValue, $convertedValue, $context),
            'enum' => $this->convertEnumDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
            'fixed' => $this->convertFixedDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
            'array' => $this->convertArrayDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
            'map' => $this->convertMapDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
            'record' => $this->convertRecordDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
            'union' => $this->convertUnionDefaultValue($defaultValue, $normalizedSchema, $convertedValue, $context),
        };

        if (!$success) {
            return false;
        }

        $logicalType = $this->schemaHelper->getLogicalType($normalizedSchema);
        if ($logicalType !== null) {
            $convertedValue = $logicalType->denormalize($convertedValue);
        }

        return true;
    }

    private function convertNullDefaultValue(mixed $defaultValue, mixed &$convertedValue, ValidationContextInterface $context): bool
    {
        if ($defaultValue !== null) {
            $context->addError('Null default value must be null');
            return false;
        }

        $convertedValue = null;
        return true;
    }

    private function convertBooleanDefaultValue(
        mixed $defaultValue,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_bool($defaultValue)) {
            $context->addError('Boolean default value must be a boolean');
            return false;
        }

        $convertedValue = $defaultValue;
        return true;
    }

    private function convertIntegerDefaultValue(
        mixed $defaultValue,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_int($defaultValue)) {
            $context->addError('Integer default value must be an integer');
            return false;
        }

        $convertedValue = $defaultValue;
        return true;
    }

    private function convertFloatDefaultValue(mixed $defaultValue, mixed &$convertedValue, ValidationContextInterface $context): bool
    {
        if (!is_float($defaultValue) && !is_int($defaultValue)) {
            $context->addError('Float default value must be a float or int');
            return false;
        }

        $convertedValue = (float) $defaultValue;
        return true;
    }

    private function convertDoubleDefaultValue(
        mixed $defaultValue,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        return $this->convertFloatDefaultValue($defaultValue, $convertedValue, $context);
    }

    private function convertStringDefaultValue(
        mixed $defaultValue,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_string($defaultValue)) {
            $context->addError('String default value must be a string');
            return false;
        }

        $convertedValue = $defaultValue;
        return true;
    }

    private function convertBytesDefaultValue(mixed $defaultValue, mixed &$convertedValue, ValidationContextInterface $context): bool
    {
        if (!is_string($defaultValue)) {
            $context->addError('Bytes default value must be a string');
            return false;
        }

        $bytes = [];
        foreach (mb_str_split($defaultValue) as $index => $char) {
            $byte = mb_ord($char);
            if ($byte < 0 || $byte > 255) {
                $context->addError("Invalid byte value at index {$index}");
                return false;
            }
            $bytes[] = $byte;
        }

        $convertedValue = pack('C*', ...$bytes);
        return true;
    }

    /**
     * @param EnumSchema $schema
     */
    private function convertEnumDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_string($defaultValue)) {
            $context->addError('Enum default value must be a string');
            return false;
        }

        if (!in_array($defaultValue, $schema['symbols'], true)) {
            $context->addError("Enum default value '{$defaultValue}' is not in symbols list");
            return false;
        }

        $convertedValue = $defaultValue;
        return true;
    }

    /**
     * @param FixedSchema $schema
     */
    private function convertFixedDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!$this->convertBytesDefaultValue($defaultValue, $convertedValue, $context)) {
            return false;
        }

        // At this point, convertedValue is guaranteed to be a string
        /** @var string $convertedValue */
        if (strlen($convertedValue) !== $schema['size']) {
            $context->addError('Fixed default value length does not match schema size');
            return false;
        }

        return true;
    }

    /**
     * @param ArraySchema $schema
     */
    private function convertArrayDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_array($defaultValue)) {
            $context->addError('Array default value must be an array');
            return false;
        }

        $result = [];
        $valid = true;
        foreach ($defaultValue as $index => $item) {
            $context->pushPath((string) $index);
            $convertedItem = null;
            if (!$this->convertDefaultValue($item, $schema['items'], $convertedItem, $context)) {
                $valid = false;
            } else {
                $result[] = $convertedItem;
            }
            $context->popPath();
        }

        if (!$valid) {
            return false;
        }

        $convertedValue = $result;
        return true;
    }

    /**
     * @param MapSchema $schema
     */
    private function convertMapDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_array($defaultValue)) {
            $context->addError('Map default value must be an array');
            return false;
        }

        $result = [];
        $valid = true;
        foreach ($defaultValue as $key => $value) {
            $stringKey = (string) $key;
            $context->pushPath($stringKey);

            $convertedMapValue = null;
            if (!$this->convertDefaultValue($value, $schema['values'], $convertedMapValue, $context)) {
                $valid = false;
            } else {
                $result[$stringKey] = $convertedMapValue;
            }
            $context->popPath();
        }

        if (!$valid) {
            return false;
        }

        $convertedValue = $result;
        return true;
    }

    /**
     * @param RecordSchema $schema
     */
    private function convertRecordDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        if (!is_array($defaultValue)) {
            $context->addError('Record default value must be an array');
            return false;
        }

        $result = [];
        $valid = true;
        foreach ($schema['fields'] as $field) {
            $fieldName = $field['name'];
            $context->pushPath($fieldName);

            $convertedFieldValue = null;
            if (array_key_exists($fieldName, $defaultValue)) {
                if (!$this->convertDefaultValue($defaultValue[$fieldName], $field['type'], $convertedFieldValue, $context)) {
                    $valid = false;
                }
            } elseif (array_key_exists('default', $field)) {
                if (!$this->convertDefaultValue($field['default'], $field['type'], $convertedFieldValue, $context)) {
                    $valid = false;
                }
            } else {
                $context->addError('Missing default value');
                $valid = false;
            }
            $result[$fieldName] = $convertedFieldValue;
            $context->popPath();
        }

        if (!$valid) {
            return false;
        }

        $convertedValue = $result;
        return true;
    }

    /**
     * @param UnionSchema $schema
     */
    private function convertUnionDefaultValue(
        mixed $defaultValue,
        array $schema,
        mixed &$convertedValue,
        ValidationContextInterface $context,
    ): bool {
        $context->pushContext();
        $valid = false;

        foreach ($schema['branches'] as $index => $branch) {
            $context->pushPath("branch {$index}");
            $branchConvertedValue = null;
            $branchValid = $this->convertDefaultValue($defaultValue, $branch, $branchConvertedValue, $context);
            $context->popPath();

            if ($branchValid) {
                $convertedValue = $branchConvertedValue;
                $valid = true;
                break;
            }
        }

        $context->popContext(discardErrors: $valid);
        return $valid;
    }
}
