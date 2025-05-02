<?php

namespace Auxmoney\Avro\Exceptions;

use Exception;

class DataMismatchException extends Exception
{
    /** @param array<string> $errors */
    public function __construct(
        public readonly array $errors,
        int $code = 0,
        Exception $previous = null,
    ) {
        $formattedErrors = array_map(fn($error) => "- $error\n", $errors);
        $message = "The provided data does not match the AVRO schema.\nErrors:\n" . implode('', $formattedErrors);
        parent::__construct($message, $code, $previous);
    }
}