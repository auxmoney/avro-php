<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Adapters\Apache\Datum;

use Auxmoney\Avro\Adapters\Apache\IO\AvroIOReaderAdapter;
use Auxmoney\Avro\Contracts\ReadableStreamInterface;

class AvroIOBinaryDecoder extends \Apache\Avro\Datum\AvroIOBinaryDecoder
{
    public function __construct(ReadableStreamInterface $readableStream)
    {
        parent::__construct(new AvroIOReaderAdapter($readableStream));
    }
}
