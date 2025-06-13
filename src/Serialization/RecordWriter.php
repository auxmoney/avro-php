<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class RecordWriter implements WriterInterface
{
    /**
     * @param array<PropertyWriter> $propertyWriters
     */
    public function __construct(
        private readonly array $propertyWriters,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(is_array($datum) || is_object($datum));
        foreach ($this->propertyWriters as $property) {
            $success = $this->getFieldValue($datum, $property, $value);
            assert($success);

            $property->write($value, $stream);
        }
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_array($datum) && !is_object($datum)) {
            $context?->addError('expected array or object, got ' . gettype($datum));
            return false;
        }

        $valid = true;
        foreach ($this->propertyWriters as $property) {
            if (!$this->getFieldValue($datum, $property, $value)) {
                if (!$property->hasDefault) {
                    $context?->addError('missing required field ' . $property->name);
                    $valid = false;
                }

                continue;
            }

            $context?->pushPath($property->name);
            $valid = $valid && $property->validate($value, $context);
            $context?->popPath();
        }

        return $valid;
    }

    /**
     * @param array<mixed>|object $datum
     */
    private function getFieldValue(array|object $datum, PropertyWriter $property, mixed &$outputValue = null): bool
    {
        if (is_array($datum)) {
            if (array_key_exists($property->name, $datum)) {
                $outputValue = $datum[$property->name];
                return true;
            }

            if ($property->hasDefault) {
                $outputValue = $property->default;
                return true;
            }

            return false;
        }

        if (property_exists($datum, $property->name)) {
            $outputValue = $datum->{$property->name};
            return true;
        }

        /** @infection-ignore-all */
        $ucfirst = ucfirst($property->name);

        $getter = 'get' . $ucfirst;
        if (method_exists($datum, $getter)) {
            $outputValue = $datum->{$getter}();
            return true;
        }

        $isser = 'is' . $ucfirst;
        if (method_exists($datum, $isser)) {
            $outputValue = $datum->{$isser}();
            return true;
        }

        $hasser = 'has' . $ucfirst;
        if (method_exists($datum, $hasser)) {
            $outputValue = $datum->{$hasser}();
            return true;
        }

        if ($property->hasDefault) {
            $outputValue = $property->default;
            return true;
        }

        return false;
    }
}
