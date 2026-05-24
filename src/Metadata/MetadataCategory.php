<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

enum MetadataCategory: string
{
    case Exif = 'EXIF';
    case Iptc = 'IPTC';
    case Xmp = 'XMP';
    case Composite = 'COMPOSITE';
}
