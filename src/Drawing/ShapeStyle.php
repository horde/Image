<?php

declare(strict_types=1);

namespace Horde\Image\Drawing;

enum ShapeStyle: string
{
    case Stroke = 'S';
    case Fill = 'F';
    case StrokeAndFill = 'SF';
}
