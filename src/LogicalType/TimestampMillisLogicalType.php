<?php

declare(strict_types=1);

namespace Auxmoney\Avro\LogicalType;

use Auxmoney\Avro\Contracts\LogicalTypeInterface;
use Auxmoney\Avro\Contracts\ValidationContextInterface;
use DateTime;
use DateTimeInterface;

class TimestampMillisLogicalType implements LogicalTypeInterface
{
    public function validate(mixed $datum, ?ValidationContextInterface $context): bool
    {
        if (is_int($datum)) {
            return true; // Already milliseconds since Unix epoch
        }

        if (is_string($datum)) {
            // Try to parse as ISO 8601 timestamp
            if (DateTime::createFromFormat(DateTime::ATOM, $datum) !== false ||
                DateTime::createFromFormat('Y-m-d H:i:s', $datum) !== false ||
                DateTime::createFromFormat('Y-m-d\TH:i:s', $datum) !== false) {
                return true;
            }
            
            $context?->addError('Invalid timestamp format. Expected ISO 8601 format or Y-m-d H:i:s');
            return false;
        }

        if ($datum instanceof DateTimeInterface) {
            return true;
        }

        $context?->addError('Timestamp value must be an integer (milliseconds since Unix epoch), timestamp string, or DateTime object');
        return false;
    }

    public function normalize(mixed $datum): mixed
    {
        if (is_int($datum)) {
            return $datum; // Already milliseconds since epoch
        }

        if ($datum instanceof DateTimeInterface) {
            return (int) ($datum->getTimestamp() * 1000 + intval($datum->format('u') / 1000));
        }

        if (is_string($datum)) {
            $date = DateTime::createFromFormat(DateTime::ATOM, $datum) ?:
                    DateTime::createFromFormat('Y-m-d H:i:s', $datum) ?:
                    DateTime::createFromFormat('Y-m-d\TH:i:s', $datum);
                    
            if ($date === false) {
                throw new \InvalidArgumentException('Invalid timestamp format');
            }
            
            return (int) ($date->getTimestamp() * 1000 + intval($date->format('u') / 1000));
        }

        throw new \InvalidArgumentException('Cannot normalize timestamp value');
    }

    public function denormalize(mixed $datum): mixed
    {
        if (!is_int($datum)) {
            throw new \InvalidArgumentException('Expected integer (milliseconds since Unix epoch) for timestamp denormalization');
        }

        $seconds = intval($datum / 1000);
        $milliseconds = $datum % 1000;
        
        $dateTime = new DateTime('@' . $seconds);
        $dateTime->setTimezone(new \DateTimeZone('UTC'));
        
        // Add milliseconds
        if ($milliseconds > 0) {
            $dateTime->modify('+' . $milliseconds . ' milliseconds');
        }
        
        return $dateTime->format('Y-m-d\TH:i:s.v\Z');
    }
}