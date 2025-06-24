<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;

class UnionWriter implements WriterInterface
{
    /**
     * @param array<WriterInterface> $branchWriters
     */
    public function __construct(
        private readonly array $branchWriters,
        private readonly BinaryEncoder $encoder,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        foreach ($this->branchWriters as $index => $branchWriter) {
            if (!$branchWriter->validate($datum)) {
                continue;
            }

            $this->encoder->writeLong($stream, $index);
            $branchWriter->write($datum, $stream);
            break;
        }
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        $context?->pushContext();
        $valid = false;

        foreach ($this->branchWriters as $index => $branchWriter) {
            $context?->pushPath("branch {$index}");
            $valid = $branchWriter->validate($datum, $context);
            $context?->popPath();
            if ($valid) {
                break;
            }
        }

        $context?->popContext(discardErrors: $valid);
        return $valid;
    }
}
