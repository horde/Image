<?php

declare(strict_types=1);

namespace Horde\Image\Sequence;

enum DisposalMethod: int
{
    case None = 0;
    case Background = 1;
    case Previous = 2;
}
