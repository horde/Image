<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata\Reader;

use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\Reader\BundledReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BundledReader::class)]
final class BundledReaderTest extends TestCase
{
    private static string $fixture;

    public static function setUpBeforeClass(): void
    {
        self::$fixture = dirname(__DIR__, 4) . '/test/Horde/Image/Fixtures/img_exif.jpg';
    }

    public function testReadFileReturnsImageMetadata(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertInstanceOf(ImageMetadata::class, $meta);
    }

    public function testSupportedCategories(): void
    {
        $reader = new BundledReader();
        $this->assertSame([MetadataCategory::Exif], $reader->supportedCategories());
    }

    public function testReadFileExtractsResolution(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::XResolution));
        $xres = $meta->get(MetadataField::XResolution);
        $this->assertNotNull($xres);
    }

    public function testReadFileExtractsGpsCoordinates(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::GPSLatitude));
        $this->assertTrue($meta->has(MetadataField::GPSLongitude));

        $gps = $meta->gps();
        $this->assertNotNull($gps);
        $this->assertEqualsWithDelta(44.353, $gps->latitude, 0.01);
        $this->assertEqualsWithDelta(68.223, $gps->longitude, 0.01);
    }

    public function testReadFileExtractsFileSize(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::FileSize));
        $this->assertSame(181401, $meta->get(MetadataField::FileSize));
    }

    public function testReadFileReturnsEmptyMetadataForInvalidPath(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile('/nonexistent/path.jpg');

        $this->assertSame([], $meta->all());
    }

    public function testReadDataReturnsOnlyFileSizeForNonJpeg(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readData('not a jpeg file');

        $this->assertFalse($meta->has(MetadataField::GPSLatitude));
        $this->assertFalse($meta->has(MetadataField::Make));
    }

    public function testReadDataFromBinaryString(): void
    {
        $reader = new BundledReader();
        $data = file_get_contents(self::$fixture);
        $meta = $reader->readData($data);

        $this->assertTrue($meta->has(MetadataField::GPSLatitude));
        $this->assertTrue($meta->has(MetadataField::GPSLongitude));
    }

    public function testReadFileResolutionUnit(): void
    {
        $reader = new BundledReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::ResolutionUnit));
        $this->assertSame(2, $meta->get(MetadataField::ResolutionUnit));
    }
}
