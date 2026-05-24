<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

interface MetadataReader
{
    public function readFile(string $path): ImageMetadata;

    public function readData(string $data): ImageMetadata;

    /**
     * @return list<MetadataCategory>
     */
    public function supportedCategories(): array;
}
