<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata\Parser;

use Horde\Image\Metadata\GpsCoordinate;
use Horde\Image\Metadata\Parser\GpsParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GpsParser::class)]
final class GpsParserTest extends TestCase
{
    public function testParseWithDmsArray(): void
    {
        $data = [
            'GPSLatitude' => ['48/1', '51/1', '24/1'],
            'GPSLatitudeRef' => 'N',
            'GPSLongitude' => ['2/1', '21/1', '7/1'],
            'GPSLongitudeRef' => 'E',
        ];

        $coord = GpsParser::parse($data);
        $this->assertInstanceOf(GpsCoordinate::class, $coord);
        $this->assertEqualsWithDelta(48.856667, $coord->latitude, 0.001);
        $this->assertEqualsWithDelta(2.351944, $coord->longitude, 0.001);
    }

    public function testParseWithSouthWest(): void
    {
        $data = [
            'GPSLatitude' => ['33/1', '52/1', '10/1'],
            'GPSLatitudeRef' => 'S',
            'GPSLongitude' => ['151/1', '12/1', '30/1'],
            'GPSLongitudeRef' => 'W',
        ];

        $coord = GpsParser::parse($data);
        $this->assertInstanceOf(GpsCoordinate::class, $coord);
        $this->assertLessThan(0, $coord->latitude);
        $this->assertLessThan(0, $coord->longitude);
    }

    public function testParseWithAltitude(): void
    {
        $data = [
            'GPSLatitude' => ['35/1', '40/1', '34/1'],
            'GPSLatitudeRef' => 'N',
            'GPSLongitude' => ['139/1', '46/1', '3/1'],
            'GPSLongitudeRef' => 'E',
            'GPSAltitude' => '40/1',
            'GPSAltitudeRef' => '0',
        ];

        $coord = GpsParser::parse($data);
        $this->assertInstanceOf(GpsCoordinate::class, $coord);
        $this->assertSame(40.0, $coord->altitude);
    }

    public function testParseWithBelowSeaLevelAltitude(): void
    {
        $data = [
            'GPSLatitude' => ['31/1', '30/1', '0/1'],
            'GPSLatitudeRef' => 'N',
            'GPSLongitude' => ['35/1', '28/1', '0/1'],
            'GPSLongitudeRef' => 'E',
            'GPSAltitude' => '420/1',
            'GPSAltitudeRef' => '1',
        ];

        $coord = GpsParser::parse($data);
        $this->assertInstanceOf(GpsCoordinate::class, $coord);
        $this->assertSame(-420.0, $coord->altitude);
    }

    public function testParseReturnsNullWhenNoGpsData(): void
    {
        $this->assertNull(GpsParser::parse([]));
        $this->assertNull(GpsParser::parse(['Make' => 'Canon']));
    }

    public function testParseReturnsNullWithPartialData(): void
    {
        $data = [
            'GPSLatitude' => ['48/1', '51/1', '24/1'],
            'GPSLatitudeRef' => 'N',
        ];
        $this->assertNull(GpsParser::parse($data));
    }

    public function testParseCoordinateWithStringFormat(): void
    {
        $result = GpsParser::parseCoordinate('48/1, 51/1, 24/1', 'N');
        $this->assertEqualsWithDelta(48.856667, $result, 0.001);
    }

    public function testParseCoordinateWithScalar(): void
    {
        $result = GpsParser::parseCoordinate(48.8566, 'N');
        $this->assertEqualsWithDelta(48.8566, $result, 0.0001);
    }

    public function testParseCoordinateReturnsNullForNullInput(): void
    {
        $this->assertNull(GpsParser::parseCoordinate(null));
        $this->assertNull(GpsParser::parseCoordinate(''));
        $this->assertNull(GpsParser::parseCoordinate([]));
    }

    public function testParseCoordinateReturnsNullForZero(): void
    {
        $this->assertNull(GpsParser::parseCoordinate(['0/1', '0/1', '0/1']));
    }

    public function testParseFraction(): void
    {
        $this->assertSame(48.0, GpsParser::parseFraction('48/1'));
        $this->assertSame(25.5, GpsParser::parseFraction('51/2'));
        $this->assertSame(5.0, GpsParser::parseFraction(5));
        $this->assertSame(3.5, GpsParser::parseFraction('3.5'));
    }

    public function testParseFractionZeroDenominator(): void
    {
        $this->assertSame(10.0, GpsParser::parseFraction('10/0'));
    }

    public function testDegToDecimal(): void
    {
        $result = GpsParser::degToDecimal(48.0, 51.0, 24.0);
        $this->assertEqualsWithDelta(48.856667, $result, 0.001);
    }
}
