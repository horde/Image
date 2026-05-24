<?php

declare(strict_types=1);

namespace Horde\Image\Driver;

use Horde\Image\Format\DecodeOptions;
use Horde\Image\Format\EncodeOptions;
use Horde\Image\Format\ImageFormat;
use Horde\Image\Sequence\AnimationOptions;
use Horde\Image\Sequence\ImageSequence;

interface SequenceDriver extends ImageDriver
{
    public function loadSequence(string $data, DecodeOptions $options = new DecodeOptions()): ImageSequence;

    public function loadFileSequence(string $path, DecodeOptions $options = new DecodeOptions()): ImageSequence;

    public function encodeSequence(
        ImageSequence $sequence,
        ImageFormat $format,
        EncodeOptions $options = new EncodeOptions(),
        AnimationOptions $animation = new AnimationOptions(),
    ): string;

    public function coalesce(ImageSequence $sequence): ImageSequence;

    public function optimize(ImageSequence $sequence): ImageSequence;
}
