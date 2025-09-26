<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Exceptions;

use Exception;

/**
 * Base exception for all Auxmoney Avro library exceptions.
 * 
 * This allows library users to catch all exceptions thrown by this library
 * using a single catch block: catch (AuxmoneyAvroException $e)
 */
abstract class AuxmoneyAvroException extends Exception
{
}