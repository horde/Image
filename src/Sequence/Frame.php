<?php

declare(strict_types=1);

namespace Horde\Image\Sequence;

use Horde\Image\Driver\ImageResource;

final readonly class Frame
{
    public function __construct(
        public ImageResource $image,
        public int $delay = 0,
        public DisposalMethod $disposal = DisposalMethod::None,
        public int $x = 0,
        public int $y = 0,
    ) {}

    public function withDelay(int $milliseconds): self
    {
        return new self($this->image, $milliseconds, $this->disposal, $this->x, $this->y);
    }

    public function withImage(ImageResource $image): self
    {
        return new self($image, $this->delay, $this->disposal, $this->x, $this->y);
    }

    public function withDisposal(DisposalMethod $disposal): self
    {
        return new self($this->image, $this->delay, $disposal, $this->x, $this->y);
    }

    public function withOffset(int $x, int $y): self
    {
        return new self($this->image, $this->delay, $this->disposal, $x, $y);
    }
}
