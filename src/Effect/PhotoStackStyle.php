<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

enum PhotoStackStyle: string
{
    case Plain = 'plain';
    case Rounded = 'rounded';
    case Polaroid = 'polaroid';
}
