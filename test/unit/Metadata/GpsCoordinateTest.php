<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata;

use Horde\Image\Metadata\GpsCoordinate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(GpsCoordinate::class)]
final class GpsCoordinateTest extends TestCase
{
    public function testConstructionWithLatLon(): void
    {
        $coord = new GpsCoordinate(48.8566, 2.3522);

        $this->assertSame(48.8566, $coord->latitude);
        $this->assertSame(2.3522, $coord->longitude);
        $this->assertNull($coord->altitude);
    }

    public function testConstructionWithAltitude(): void
    {
        $coord = new GpsCoordinate(35.6762, 139.6503, 40.5);

        $this->assertSame(35.6762, $coord->latitude);
        $this->assertSame(139.6503, $coord->longitude);
        $this->assertSame(40.5, $coord->altitude);
    }

    public function testNegativeCoordinates(): void
    {
        $coord = new GpsCoordinate(-33.8688, 151.2093);

        $this->assertSame(-33.8688, $coord->latitude);
        $this->assertSame(151.2093, $coord->longitude);
    }

    public function testToArray(): void
    {
        $coord = new GpsCoordinate(51.5074, -0.1278, 11.0);

        $this->assertSame([
            'latitude' => 51.5074,
            'longitude' => -0.1278,
            'altitude' => 11.0,
        ], $coord->toArray());
    }

    public function testToArrayWithoutAltitude(): void
    {
        $coord = new GpsCoordinate(40.7128, -74.006);

        $this->assertSame([
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'altitude' => null,
        ], $coord->toArray());
    }

    public function testIsReadonly(): void
    {
        $ref = new ReflectionClass(GpsCoordinate::class);
        $this->assertTrue($ref->isReadOnly());
    }
}
