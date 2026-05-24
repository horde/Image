<?php

declare(strict_types=1);

namespace Horde\Image\Metadata\Parser;

interface MakerNoteParser
{
    public function supports(string $makeLower): bool;

    /**
     * @return array<string, mixed>
     */
    public function parse(string $data, bool $intel): array;
}
