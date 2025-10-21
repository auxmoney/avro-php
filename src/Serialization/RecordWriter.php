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
            $value = $this->getFieldValue($datum, $property);
            $property->write($value, $stream);
        }
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_array($datum) && !is_object($datum)) {
            $context?->addError('expected array or object, got ' . gettype($datum));
            return false;
        }

        if ($context === null) {
            // When no context is provided, short-circuit for performance
            foreach ($this->propertyWriters as $property) {
                $value = $this->getFieldValue($datum, $property);
                if (!$property->validate($value, $context)) {
                    return false; // Early exit on validation failure
                }
            }
            return true;
        }

        // When context is provided, continue through all properties to collect all errors
        $valid = true;
        foreach ($this->propertyWriters as $property) {
            $value = $this->getFieldValue($datum, $property);
            $context->pushPath($property->name);
            $valid = $property->validate($value, $context) && $valid;
            $context->popPath();
        }

        return $valid;
    }

    /**
     * @param array<mixed>|object $datum
     */
    private function getFieldValue(array|object $datum, PropertyWriter $property): mixed
    {
        if (is_array($datum)) {
            if (array_key_exists($property->name, $datum)) {
                return $datum[$property->name];
            }

            return null;
        }

        if (isset($datum->{$property->name})) {
            return $datum->{$property->name};
        }

        /** @infection-ignore-all */
        $ucfirst = ucfirst($property->name);

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
