<?php

declare(strict_types=1);

namespace Horde\Image\Color;

enum ColorModel: string
{
    case Rgb = 'rgb';
    case Rgba = 'rgba';
    case Cmyk = 'cmyk';
    case Gray = 'gray';
}
