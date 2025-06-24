<?php

declare(strict_types=1);

namespace Auxmoney\Avro\Serialization;

use Auxmoney\Avro\Contracts\ValidationContextInterface;
use Auxmoney\Avro\Contracts\WritableStreamInterface;
use Auxmoney\Avro\Contracts\WriterInterface;
use Auxmoney\Avro\Exceptions\DataMismatchException;
use Auxmoney\Avro\IO\WritableStringBuffer;
use Countable;
use Generator;
use Traversable;

class ArrayWriter implements WriterInterface
{
    public function __construct(
        private readonly WriterInterface $itemWriter,
        private readonly BinaryEncoder $encoder,
        private readonly int $blockCount,
        private readonly bool $writeBlockSize,
    ) {
    }

    public function write(mixed $datum, WritableStreamInterface $stream): void
    {
        assert(
            is_array($datum) || ($datum instanceof Traversable && $datum instanceof Countable),
            'ArrayWriter expects an array or countable traversable, got ' . gettype($datum),
        );

        $this->writeBlockSize ? $this->writeWithBlockSizes($datum, $stream) : $this->writeWithoutBlockSizes($datum, $stream);
        $this->encoder->writeLong($stream, 0);
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
     * @param array<mixed>|(Traversable<mixed>&Countable) $datum
     * @throws DataMismatchException
     */
    private function writeWithBlockSizes(array|(Traversable&Countable) $datum, WritableStreamInterface $stream): void
    {
        foreach ($this->getBlocksGenerator($datum) as $block) {
            $this->encoder->writeLong($stream, -count($block));
            if (count($block) === 0) {
                continue;
            }

            $buffer = new WritableStringBuffer();
            foreach ($block as $item) {
                $this->itemWriter->write($item, $buffer);
            }
            $this->encoder->writeString($stream, $buffer->__toString());
        }
    }

    /**
     * @param array<mixed>|(Traversable<mixed>&Countable) $datum
     * @throws DataMismatchException
     */
    private function writeWithoutBlockSizes(array|(Traversable&Countable) $datum, WritableStreamInterface $stream): void
    {
        foreach ($this->getBlocksGenerator($datum) as $block) {
            $this->encoder->writeLong($stream, count($block));
            foreach ($block as $item) {
                $this->itemWriter->write($item, $stream);
            }
        }
    }

    /**
     * @param array<mixed>|(Traversable<mixed>&Countable) $datum
     * @return Generator<array<mixed>|(Traversable<mixed>&Countable)>
     */
    private function getBlocksGenerator(array|(Traversable&Countable) $datum): Generator
    {
        if ($this->blockCount <= 0) {
            if (count($datum) > 0) {
                yield $datum;
            }
            return;
        }

        $block = [];
        foreach ($datum as $item) {
            $block[] = $item;

            if (count($block) >= $this->blockCount) {
                yield $block;
                $block = [];
            }
        }

        if (!empty($block)) {
            yield $block;
        }
    }
}
