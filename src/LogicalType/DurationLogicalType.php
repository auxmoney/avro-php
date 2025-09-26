<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;

class DurationLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_string($datum) && strlen($datum) === 12) {
            return true; // Already 12-byte fixed representation
        }

        if (is_array($datum)) {
            // Array with [months, days, milliseconds]
            if (count($datum) === 3 && 
                is_int($datum[0]) && is_int($datum[1]) && is_int($datum[2]) &&
                $datum[0] >= 0 && $datum[1] >= 0 && $datum[2] >= 0) {
                return true;
            }
            
            $context?->addError('Duration array must contain exactly 3 non-negative integers: [months, days, milliseconds]');
            return false;
        }

        if (is_object($datum) && method_exists($datum, 'getMonths') && 
            method_exists($datum, 'getDays') && method_exists($datum, 'getMilliseconds')) {
            return true; // Duration object with required methods
        }

        $context?->addError('Duration value must be a 12-byte string, array [months, days, milliseconds], or duration object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        assert(
            (is_string($datum) && strlen($datum) === 12) ||
            is_array($datum) ||
            (is_object($datum) && method_exists($datum, 'getMonths') && 
             method_exists($datum, 'getDays') && method_exists($datum, 'getMilliseconds'))
        );
        
        if (is_string($datum) && strlen($datum) === 12) {
            return $datum; // Already in correct format
        }

        $months = 0;
        $days = 0;
        $milliseconds = 0;

        if (is_array($datum)) {
            [$months, $days, $milliseconds] = $datum;
        } elseif (is_object($datum)) {
            $months = $datum->getMonths();
            $days = $datum->getDays();
            $milliseconds = $datum->getMilliseconds();
        }

        // Pack as 3 little-endian unsigned 32-bit integers (12 bytes total)
        return pack('VVV', $months, $days, $milliseconds);
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_string($datum) || strlen($datum) !== 12) {
            throw new \InvalidArgumentException('Expected 12-byte string for duration denormalization');
        }

        // Unpack 3 little-endian unsigned 32-bit integers
        $values = unpack('V3', $datum);
        
        return [
            'months' => $values[1],
            'days' => $values[2], 
            'milliseconds' => $values[3]
        ];
    }
}