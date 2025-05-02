<?php

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class ArrayWriter implements WriterInterface
{
    public function __construct(
        private readonly WriterInterface $itemWriter,
        private readonly BinaryEncoder $encoder,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(is_array($datum) || is_object($datum));

        $stream->write($this->encoder->encodeLong(count($datum)));

        foreach ($datum as $item) {
            $this->itemWriter->write($item, $stream);
        }
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_iterable($datum)) {
            $context?->addError('expected iterable, got ' . gettype($datum));
            return false;
        }

        $valid = true;
        foreach ($datum as $index => $item) {
            $context?->pushPath("[$index]");
            $valid = $valid && $this->itemWriter->validate($item, $context);
            $context?->popPath();
        }

        return $valid;
    }
}