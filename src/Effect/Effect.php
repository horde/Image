<?php

declare(strict_types=1);

namespace Horde\Image\Effect;

use Horde\Image\Driver\ImageResource;

/**
 * Open plugin interface for image effects.
 *
 * Unlike Filters (which are closed — the driver must know each type),
 * Effects are open: any class implementing this interface can be applied
 * to an ImageResource. User-supplied effects work without library changes.
 */
interface Effect
{
    public function apply(ImageResource $image): ImageResource;
}
