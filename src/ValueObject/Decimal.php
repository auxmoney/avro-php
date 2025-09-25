<?php

declare(strict_types=1);

namespace Auxmoney\Avro\ValueObject;

use Auxmoney\Avro\Exceptions\InvalidArgumentException;

readonly class Decimal
{
    private ArbitraryPrecisionInteger $unscaledValue;
    private int $scale;

    public function __construct(ArbitraryPrecisionInteger $unscaledValue, int $scale)
    {
        if ($scale < 0) {
            throw new InvalidArgumentException('Scale must be non-negative');
        }

        $this->unscaledValue = $unscaledValue;
        $this->scale = $scale;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @throws InvalidArgumentException if the input is not a valid decimal string
     */
    public static function fromDecimalRepresentation(string $value): self
    {
        if (!preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            throw new InvalidArgumentException('Invalid decimal format');
        }

        $isNegative = str_starts_with($value, '-');
        $absValue = ltrim($value, '-');

        // Split into integer and fractional parts
        $parts = explode('.', $absValue);
        $integerPart = $parts[0];
        $fractionalPart = rtrim($parts[1] ?? '', '0');

        // Use actual decimal places as scale
        $scale = strlen($fractionalPart);

        // Create unscaled integer string
        $unscaled = $integerPart . $fractionalPart;
        $unscaled = ltrim($unscaled, '0') ?: '0';

        if ($isNegative && $unscaled !== '0') {
            $unscaled = '-' . $unscaled;
        }

        return new self(ArbitraryPrecisionInteger::fromDecimalRepresentation($unscaled), $scale);
    }

    public static function fromInteger(int $value): self
    {
        $unscaledValue = ArbitraryPrecisionInteger::fromInteger($value);
        return new self($unscaledValue, 0);
    }

    public static function fromFloat(float $value, int $decimals): self
    {
        if (!is_finite($value)) {
            throw new InvalidArgumentException('Float value must be finite');
        }

        $stringValue = number_format($value, $decimals, '.', '');

        return self::fromDecimalRepresentation($stringValue);
    }

    public function getUnscaledValue(): ArbitraryPrecisionInteger
    {
        return $this->unscaledValue;
    }

    public function isNegative(): bool
    {
        return $this->unscaledValue->isNegative();
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function toString(): string
    {
        if ($this->scale === 0) {
            return $this->unscaledValue->toString();
        }

        $isNegative = $this->unscaledValue->isNegative();

        $digits = $this->unscaledValue->toAbsoluteDecimalDigits();
        $digits = array_pad($digits, -$this->scale - 1, 0);

        $decimalPlaces = $this->scale;
        $last = count($digits) - 1;
        while ($decimalPlaces > 0 && $last > 0 && $digits[$last] === 0) {
            $decimalPlaces--;
            $last--;
        }

        foreach ($digits as $index => $digit) {
            $digits[$index] = 48 + $digit; // Convert to ASCII
        }

        if ($decimalPlaces > 0) {
            array_splice($digits, -$this->scale, 0, 46); // Insert decimal point (ASCII 46)
            $last++;
        }

        $digits = array_slice($digits, 0, $last + 1);
        if ($isNegative) {
            array_unshift($digits, 45); // Insert minus sign (ASCII 45)
        }

        return pack('C*', ...$digits);
    }

    public function toBytes(?int $padLength = null): string
    {
        return $this->unscaledValue->toBytes($padLength);
    }

    public static function fromBytes(string $bytes, int $scale): self
    {
        $unscaledValue = ArbitraryPrecisionInteger::fromBytes($bytes);
        return new self($unscaledValue, $scale);
    }

    public function withScale(int $newScale): self
    {
        if ($newScale < 0) {
            throw new InvalidArgumentException('Scale must be non-negative');
        }

        if ($newScale === $this->scale) {
            return $this;
        }

        // Calculate scale difference and shift the decimal position accordingly
        $scaleDelta = $newScale - $this->scale;
        $newUnscaledValue = $this->unscaledValue->shiftDecimalPosition($scaleDelta);

        return new self($newUnscaledValue, $newScale);
    }
}
