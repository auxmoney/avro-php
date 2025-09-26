<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class LocalTimestampMicrosLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already microseconds since Unix epoch
        }

        if (is_string($datum)) {
            // Try to parse as local timestamp (without timezone info)
            if (DateTime::createFromFormat('Y-m-d H:i:s', $datum) !== false ||
                DateTime::createFromFormat('Y-m-d\TH:i:s', $datum) !== false ||
                DateTime::createFromFormat('Y-m-d H:i:s.u', $datum) !== false) {
                return true;
            }
            
            $context?->addError('Invalid local timestamp format. Expected Y-m-d H:i:s format (without timezone)');
            return false;
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Local timestamp value must be an integer (microseconds), timestamp string (without timezone), or DateTime object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already microseconds
        }

        if ($datum instanceof DateTimeInterface) {
            // Local timestamp ignores timezone, treats as local time
            $localTime = new DateTime($datum->format('Y-m-d H:i:s.u'));
            return (int) ($localTime->getTimestamp() * 1000000 + (int) $localTime->format('u'));
        }

        if (is_string($datum)) {
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $datum) ?:
                    DateTime::createFromFormat('Y-m-d\TH:i:s', $datum) ?:
                    DateTime::createFromFormat('Y-m-d H:i:s.u', $datum);
                    
            if ($date === false) {
                throw new \InvalidArgumentException('Invalid local timestamp format');
            }
            
            return (int) ($date->getTimestamp() * 1000000 + (int) $date->format('u'));
        }

        throw new \InvalidArgumentException('Cannot normalize local timestamp value');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (microseconds) for local timestamp denormalization');
        }

        $seconds = intval($datum / 1000000);
        $microseconds = $datum % 1000000;
        
        $dateTime = new DateTime('@' . $seconds);
        
        // Format as local timestamp (no timezone indicator)
        if ($microseconds > 0) {
            return $dateTime->format('Y-m-d H:i:s') . '.' . str_pad((string) $microseconds, 6, '0', STR_PAD_LEFT);
        } else {
            return $dateTime->format('Y-m-d H:i:s');
        }
    }
}