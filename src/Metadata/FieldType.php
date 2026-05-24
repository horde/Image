<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

enum FieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Gps = 'gps';
    case Array_ = 'array';
}
