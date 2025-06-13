<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Generator;

class ArrayWriter implements WriterInterface
{
    public const BLOCK_SIZE = 100;

    public function __construct(
        private readonly WriterInterface $itemWriter,
        private readonly BinaryEncoder $encoder,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(is_iterable($datum));

        foreach ($this->getBlocksGenerator($datum) as $block) {
            $stream->write($this->encoder->encodeLong(count($block)));
            foreach ($block as $item) {
                $this->itemWriter->write($item, $stream);
            }
        }
    }

    public function validate(mixed $datum, ?ValidationContextInterface $context = null): bool
    {
        if (!is_iterable($datum)) {
            $context?->addError('expected iterable, got ' . gettype($datum));
            return false;
        }

        if ($datum instanceof Generator) {
            $context?->addError('generators cannot be used as array values because they are not iterable multiple times');
            return false;
        }

        $valid = true;
        $index = 0;
        foreach ($datum as $item) {
            $context?->pushPath("[#{$index}]");
            $valid = $valid && $this->itemWriter->validate($item, $context);
            $context?->popPath();
            $index++;
        }

        return $valid;
    }

    /**
     * @param iterable<mixed> $datum
     * @return Generator<array<mixed>>
     */
    private function getBlocksGenerator(iterable $datum): Generator
    {
        $block = [];
        foreach ($datum as $item) {
            $block[] = $item;

            if (count($block) >= self::BLOCK_SIZE) {
                yield $block;
                $block = [];
            }
        }

        if (!empty($block)) {
            yield $block;
        }

        yield [];
    }
}
