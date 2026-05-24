<?php

declare(strict_types=1);

namespace Horde\Image\Sequence;

final readonly class AnimationOptions
{
    public function __construct(
        public int $loopCount = 0,
        public int $defaultDelay = 100,
        public bool $optimize = true,
    ) {}
}
