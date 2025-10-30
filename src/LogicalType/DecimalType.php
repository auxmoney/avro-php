<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\ValueObject\Decimal;

class DecimalType implements LogicalTypeInterface
{
    private int $precision;
    private int $scale;
    private ?int $size;

    public function __construct(int $precision, int $scale = 0, ?int $size = null)
    {
        $this->precision = $precision;
        $this->scale = $scale;
        $this->size = $size;
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (!$datum instanceof Decimal) {
            $context?->addError('Decimal value must be an instance of Decimal');
            return false;
        }

        // For fixed schemas, validate that the decimal value doesn't exceed the allowed size
        if ($this->size !== null) {
            $bytes = $datum->withScale($this->scale)->toBytes();
            $currentLength = strlen($bytes);

            if ($currentLength > $this->size) {
                $context?->addError("Decimal value requires {$currentLength} bytes but fixed schema only allows {$this->size} bytes");
                return false;
            }
        }

        return true;
    }

    public function normalize(mixed $datum): string
    {
        assert($datum instanceof Decimal, 'Expected Decimal, got ' . gettype($datum));

        return $datum->withScale($this->scale)->toBytes($this->size);
    }

    public function denormalize(mixed $datum): Decimal
    {
        assert(is_string($datum), 'Expected bytes string for decimal denormalization');

        return Decimal::fromBytes($datum, $this->scale);
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }
}
