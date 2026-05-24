<?php

declare(strict_types=1);

/**
 * Sobel edge detection filter direction.
 *
 * Copyright 2026 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 */

namespace Horde\Image\Filter;

enum SobelDirection
{
    case Horizontal;
    case Vertical;
}
