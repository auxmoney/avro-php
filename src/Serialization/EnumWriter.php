<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use BackedEnum;

class EnumWriter implements WriterInterface
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private readonly array $values,
        private readonly BinaryEncoder $encoder,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        $rawValue = $this->toRawValue($datum);
        $index = array_search($rawValue, $this->values, true);
        assert(is_int($index) && $index >= 0, 'Invalid enum value');

        $this->encoder->writeLong($stream, $index);
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        $rawValue = $this->toRawValue($datum);
        if (!is_string($rawValue)) {
            $context?->addError('expected string or BackedEnum, got ' . gettype($rawValue));
            return false;
        }

        if (!in_array($rawValue, $this->values, true)) {
            $context?->addError('invalid enum value: ' . $rawValue);
            return false;
        }

        return true;
    }

    private function toRawValue(mixed $datum): mixed
    {
        return $datum instanceof BackedEnum ? $datum->value : $datum;
    }
}
