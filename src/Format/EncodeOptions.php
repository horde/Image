<?php

declare(strict_types=1);

namespace Horde\Image\Format;

final class EncodeOptions
{
    public function __construct(
        public readonly ?int $quality = null,
        public readonly bool $progressive = false,
        public readonly bool $stripMetadata = false,
    ) {}
}
