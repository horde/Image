<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

enum BrushShape: string
{
    case Square = 'square';
    case Circle = 'circle';
    case Diamond = 'diamond';
    case Triangle = 'triangle';
}
