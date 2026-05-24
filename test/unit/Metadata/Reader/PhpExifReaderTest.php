<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata\Reader;

use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\Reader\PhpExifReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpExifReader::class)]
#[RequiresPhpExtension('exif')]
final class PhpExifReaderTest extends TestCase
{
    private static string $fixture;

    public static function setUpBeforeClass(): void
    {
        self::$fixture = dirname(__DIR__, 4) . '/test/Horde/Image/Fixtures/img_exif.jpg';
    }

    public function testReadFileReturnsImageMetadata(): void
    {
        $reader = new PhpExifReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertInstanceOf(ImageMetadata::class, $meta);
    }

    public function testSupportedCategories(): void
    {
        $reader = new PhpExifReader();
        $this->assertSame([MetadataCategory::Exif], $reader->supportedCategories());
    }

    public function testReadFileExtractsResolution(): void
    {
        $reader = new PhpExifReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::XResolution));
        $this->assertTrue($meta->has(MetadataField::YResolution));
        $this->assertTrue($meta->has(MetadataField::ResolutionUnit));
    }

    public function testReadFileExtractsGpsCoordinates(): void
    {
        $reader = new PhpExifReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::GPSLatitude));
        $this->assertTrue($meta->has(MetadataField::GPSLongitude));

        $gps = $meta->gps();
        $this->assertNotNull($gps);
        $this->assertEqualsWithDelta(44.353, $gps->latitude, 0.01);
        $this->assertEqualsWithDelta(68.223, $gps->longitude, 0.01);
    }

    public function testReadFileReturnsEmptyMetadataForInvalidPath(): void
    {
        $reader = new PhpExifReader();
        $meta = $reader->readFile('/nonexistent/path.jpg');

        $this->assertSame([], $meta->all());
    }

    public function testReadDataFromBinaryString(): void
    {
        $reader = new PhpExifReader();
        $data = file_get_contents(self::$fixture);
        $meta = $reader->readData($data);

        $this->assertTrue($meta->has(MetadataField::GPSLatitude));
        $this->assertTrue($meta->has(MetadataField::GPSLongitude));
    }
}
