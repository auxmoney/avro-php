<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;

class DecimalLogicalType implements LogicalTypeInterface
{
    private int $precision;
    private int $scale;

    public function __construct(int $precision, int $scale = 0)
    {
        $this->precision = $precision;
        $this->scale = $scale;
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (!is_string($datum) && !is_numeric($datum)) {
            $context?->addError('Decimal value must be numeric or string representation of a number');
            return false;
        }

        $stringValue = (string) $datum;

        // Check if it's a valid decimal format
        if (!preg_match('/^-?\d+(\.\d+)?$/', $stringValue)) {
            $context?->addError('Invalid decimal format');
            return false;
        }

        // Check precision and scale
        $parts = explode('.', $stringValue);
        $integerPart = ltrim($parts[0], '-0') ?: '0';
        $fractionalPart = $parts[1] ?? '';

        $totalDigits = strlen($integerPart) + strlen($fractionalPart);
        if ($totalDigits > $this->precision) {
            $context?->addError("Decimal precision {$totalDigits} exceeds maximum precision {$this->precision}");
            return false;
        }

        if (strlen($fractionalPart) > $this->scale) {
            $context?->addError("Decimal scale " . strlen($fractionalPart) . " exceeds maximum scale {$this->scale}");
            return false;
        }

        return true;
    }

    public function normalize(mixed $datum): mixed
    {
        // Convert to string representation for consistent handling
        $stringValue = (string) $datum;
        
        // Parse the decimal value
        $isNegative = str_starts_with($stringValue, '-');
        $absValue = ltrim($stringValue, '-');
        
        // Split into integer and fractional parts
        $parts = explode('.', $absValue);
        $integerPart = $parts[0] ?? '0';
        $fractionalPart = $parts[1] ?? '';
        
        // Pad fractional part to scale
        $fractionalPart = str_pad($fractionalPart, $this->scale, '0');
        
        // Create unscaled integer string
        $unscaled = $integerPart . $fractionalPart;
        $unscaled = ltrim($unscaled, '0') ?: '0';
        
        if ($isNegative && $unscaled !== '0') {
            $unscaled = '-' . $unscaled;
        }
        
        // Convert to two's complement big-endian bytes
        return $this->integerToBytes($unscaled);
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_string($datum)) {
            throw new \InvalidArgumentException('Expected bytes string for decimal denormalization');
        }
        
        // Convert bytes to integer
        $unscaled = $this->bytesToInteger($datum);
        
        // Apply scale
        if ($this->scale === 0) {
            return $unscaled;
        }
        
        $isNegative = str_starts_with($unscaled, '-');
        $absUnscaled = ltrim($unscaled, '-');
        
        // Pad with zeros if needed
        $absUnscaled = str_pad($absUnscaled, $this->scale + 1, '0', STR_PAD_LEFT);
        
        // Insert decimal point
        $integerPart = substr($absUnscaled, 0, -$this->scale) ?: '0';
        $fractionalPart = substr($absUnscaled, -$this->scale);
        
        $result = $integerPart . '.' . $fractionalPart;
        
        // Remove trailing zeros from fractional part
        $result = rtrim($result, '0');
        $result = rtrim($result, '.');
        
        return $isNegative && $result !== '0' ? '-' . $result : $result;
    }

    private function integerToBytes(string $value): string
    {
        if ($value === '0') {
            return "\x00";
        }
        
        $isNegative = str_starts_with($value, '-');
        $absValue = ltrim($value, '-');
        
        // Set BC math scale to 0 for integer operations
        $oldScale = bcscale(0);
        
        // Convert to binary representation
        $bytes = [];
        while (bccomp($absValue, '0') > 0) {
            $bytes[] = (int) bcmod($absValue, '256');
            $absValue = bcdiv($absValue, '256');
        }
        
        // Restore original scale
        bcscale($oldScale);
        
        $bytes = array_reverse($bytes);
        
        // Handle two's complement for negative numbers
        if ($isNegative) {
            // One's complement
            for ($i = 0; $i < count($bytes); $i++) {
                $bytes[$i] = 255 - $bytes[$i];
            }
            
            // Add one (two's complement)
            $carry = 1;
            for ($i = count($bytes) - 1; $i >= 0 && $carry; $i--) {
                $bytes[$i] += $carry;
                if ($bytes[$i] > 255) {
                    $bytes[$i] = $bytes[$i] % 256;
                    $carry = 1;
                } else {
                    $carry = 0;
                }
            }
            
            // Extend sign bit if needed
            if ($carry || ($bytes[0] & 0x80) === 0) {
                array_unshift($bytes, 255);
            }
        } else {
            // Ensure positive numbers don't have sign bit set
            if (($bytes[0] & 0x80) !== 0) {
                array_unshift($bytes, 0);
            }
        }
        
        return pack('C*', ...$bytes);
    }

    private function bytesToInteger(string $bytes): string
    {
        if (empty($bytes)) {
            return '0';
        }
        
        $byteArray = array_values(unpack('C*', $bytes));
        
        // Check if negative (sign bit set)
        $isNegative = ($byteArray[0] & 0x80) !== 0;
        
        if ($isNegative) {
            // Two's complement: invert all bits and add 1
            for ($i = 0; $i < count($byteArray); $i++) {
                $byteArray[$i] = 255 - $byteArray[$i];
            }
            
            // Add 1
            $carry = 1;
            for ($i = count($byteArray) - 1; $i >= 0 && $carry; $i--) {
                $byteArray[$i] += $carry;
                if ($byteArray[$i] > 255) {
                    $byteArray[$i] = $byteArray[$i] % 256;
                    $carry = 1;
                } else {
                    $carry = 0;
                }
            }
        }
        
        // Set BC math scale to 0 for integer operations
        $oldScale = bcscale(0);
        
        // Convert bytes to integer string
        $result = '0';
        foreach ($byteArray as $byte) {
            $result = bcadd(bcmul($result, '256'), (string) $byte);
        }
        
        // Restore original scale
        bcscale($oldScale);
        
        return $isNegative ? '-' . $result : $result;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }
}