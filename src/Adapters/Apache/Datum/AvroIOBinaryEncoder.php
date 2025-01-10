<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Adapters\Apache\Datum;

use Auxmoney\Avro\Adapters\Apache\IO\AvroIOWriterAdapter;
use Auxmoney\Avro\Contracts\WritableStreamInterface;

class AvroIOBinaryEncoder extends \Apache\Avro\Datum\AvroIOBinaryEncoder
{
    public function __construct(WritableStreamInterface $writableStream)
    {
        parent::__construct(new AvroIOWriterAdapter($writableStream));
    }
}
