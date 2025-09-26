<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class TimeMicrosLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            // Check if it's a valid microseconds since midnight (0-86399999999)
            if ($datum >= 0 && $datum <= 86399999999) {
                return true;
            }
            
            $context?->addError('Time microseconds must be between 0 and 86399999999 (microseconds in a day)');
            return false;
        }

        if (is_string($datum)) {
            // Try to parse as time string (HH:mm:ss.SSSSSS)
            if (preg_match('/^([01]?\d|2[0-3]):([0-5]?\d):([0-5]?\d)(\.\d{1,6})?$/', $datum)) {
                return true;
            }
            
            $context?->addError('Invalid time format. Expected HH:mm:ss.SSSSSS format');
            return false;
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Time value must be an integer (microseconds since midnight), time string (HH:mm:ss.SSSSSS), or DateTime object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already microseconds since midnight
        }

        if ($datum instanceof DateTimeInterface) {
            $hours = (int) $datum->format('H');
            $minutes = (int) $datum->format('i');
            $seconds = (int) $datum->format('s');
            $microseconds = (int) $datum->format('u');
            
            return ($hours * 3600 + $minutes * 60 + $seconds) * 1000000 + $microseconds;
        }

        if (is_string($datum)) {
            if (preg_match('/^(\d{1,2}):(\d{1,2}):(\d{1,2})(\.\d{1,6})?$/', $datum, $matches)) {
                $hours = (int) $matches[1];
                $minutes = (int) $matches[2];
                $seconds = (int) $matches[3];
                $microseconds = isset($matches[4]) ? (int) (str_pad(substr($matches[4], 1), 6, '0')) : 0;
                
                return ($hours * 3600 + $minutes * 60 + $seconds) * 1000000 + $microseconds;
            }
            
            throw new \InvalidArgumentException('Invalid time format. Expected HH:mm:ss.SSSSSS');
        }

        throw new \InvalidArgumentException('Cannot normalize time value');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (microseconds since midnight) for time denormalization');
        }

        $totalSeconds = intval($datum / 1000000);
        $microseconds = $datum % 1000000;
        
        $hours = intval($totalSeconds / 3600);
        $minutes = intval(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        
        return sprintf('%02d:%02d:%02d.%06d', $hours, $minutes, $seconds, $microseconds);
    }
}