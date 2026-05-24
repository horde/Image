<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata\Reader;

use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataCategory;
use Horde\Image\Metadata\MetadataField;
use Horde\Image\Metadata\Reader\ImagickReader;
use Imagick;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImagickReader::class)]
#[RequiresPhpExtension('imagick')]
final class ImagickReaderTest extends TestCase
{
    private static string $fixture;

    public static function setUpBeforeClass(): void
    {
        self::$fixture = dirname(__DIR__, 4) . '/test/Horde/Image/Fixtures/img_exif.jpg';
    }

    public function testReadFileReturnsImageMetadata(): void
    {
        $reader = new ImagickReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertInstanceOf(ImageMetadata::class, $meta);
    }

    public function testSupportedCategories(): void
    {
        $reader = new ImagickReader();
        $categories = $reader->supportedCategories();

        $this->assertContains(MetadataCategory::Exif, $categories);
    }

    public function testReadFileExtractsResolution(): void
    {
        $reader = new ImagickReader();
        $meta = $reader->readFile(self::$fixture);

        $this->assertTrue($meta->has(MetadataField::XResolution));
    }

    public function testReadFileReturnsEmptyForInvalidPath(): void
    {
        $reader = new ImagickReader();
        $meta = $reader->readFile('/nonexistent/path.jpg');

        $this->assertSame([], $meta->all());
    }

    public function testReadDataFromBinaryString(): void
    {
        $reader = new ImagickReader();
        $data = file_get_contents(self::$fixture);
        $meta = $reader->readData($data);

        $this->assertInstanceOf(ImageMetadata::class, $meta);
        $allData = $meta->all();
        $this->assertNotEmpty($allData);
    }
}
