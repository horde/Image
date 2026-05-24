<?php

declare(strict_types=1);

namespace Horde\Image\Metadata;

final readonly class GpsCoordinate
{
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?float $altitude = null,
    ) {}

    /**
     * @return array{latitude: float, longitude: float, altitude: ?float}
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'altitude' => $this->altitude,
        ];
    }
}
