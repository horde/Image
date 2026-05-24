<?php

declare(strict_types=1);

namespace Horde\Image\Sequence;

use ArrayIterator;
use Countable;
use Horde\Image\Driver\ImageResource;
use Horde\Image\Geometry\Size;
use Horde\Image\ImageException;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, Frame>
 */
final class ImageSequence implements Countable, IteratorAggregate
{
    /** @var list<Frame> */
    private readonly array $frames;

    /**
     * @param array<Frame> $frames
     */
    private function __construct(
        array $frames,
        private readonly Size $canvasSize,
    ) {
        if ($frames === []) {
            throw new ImageException('ImageSequence requires at least one frame');
        }
        $this->frames = array_values($frames);
    }

    /**
     * @param list<Frame> $frames
     */
    public static function fromFrames(array $frames, Size $canvasSize): self
    {
        return new self($frames, $canvasSize);
    }

    public static function single(ImageResource $image): self
    {
        return new self(
            [new Frame($image)],
            $image->size(),
        );
    }

    public function canvasSize(): Size
    {
        return $this->canvasSize;
    }

    public function count(): int
    {
        return count($this->frames);
    }

    public function frameAt(int $index): Frame
    {
        if ($index < 0 || $index >= count($this->frames)) {
            throw new ImageException(
                "Frame index {$index} out of bounds (0.." . (count($this->frames) - 1) . ")"
            );
        }
        return $this->frames[$index];
    }

    public function first(): Frame
    {
        return $this->frames[0];
    }

    /**
     * @return list<Frame>
     */
    public function frames(): array
    {
        return $this->frames;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->frames);
    }

    public function append(Frame $frame): self
    {
        return new self([...$this->frames, $frame], $this->canvasSize);
    }

    public function withFrameAt(int $index, Frame $frame): self
    {
        if ($index < 0 || $index >= count($this->frames)) {
            throw new ImageException("Frame index {$index} out of bounds");
        }
        $frames = $this->frames;
        $frames[$index] = $frame;
        return new self($frames, $this->canvasSize);
    }

    /**
     * @param callable(Frame, int): Frame $fn
     */
    public function map(callable $fn): self
    {
        $mapped = [];
        foreach ($this->frames as $i => $frame) {
            $mapped[] = $fn($frame, $i);
        }
        return new self($mapped, $this->canvasSize);
    }

    public function slice(int $offset, ?int $length = null): self
    {
        $sliced = array_slice($this->frames, $offset, $length);
        return new self($sliced, $this->canvasSize);
    }

    public function withCanvasSize(Size $size): self
    {
        return new self($this->frames, $size);
    }
}
