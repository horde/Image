<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Metadata;

use DateTimeImmutable;
use Horde\Image\Metadata\GpsCoordinate;
use Horde\Image\Metadata\ImageMetadata;
use Horde\Image\Metadata\MetadataField;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImageMetadata::class)]
final class ImageMetadataTest extends TestCase
{
    public function testEmptyConstruction(): void
    {
        $meta = new ImageMetadata();

        $this->assertSame([], $meta->all());
        $this->assertNull($meta->get(MetadataField::Make));
        $this->assertFalse($meta->has(MetadataField::Make));
    }

    public function testGetAndHas(): void
    {
        $meta = new ImageMetadata([
            'Make' => 'Canon',
            'Model' => 'EOS 5D',
        ]);

        $this->assertTrue($meta->has(MetadataField::Make));
        $this->assertSame('Canon', $meta->get(MetadataField::Make));
        $this->assertTrue($meta->has(MetadataField::Model));
        $this->assertSame('EOS 5D', $meta->get(MetadataField::Model));
        $this->assertFalse($meta->has(MetadataField::Flash));
        $this->assertNull($meta->get(MetadataField::Flash));
    }

    public function testAll(): void
    {
        $data = ['Make' => 'Nikon', 'ISOSpeedRatings' => 400];
        $meta = new ImageMetadata($data);

        $this->assertSame($data, $meta->all());
    }

    public function testGpsReturnsCoordinate(): void
    {
        $meta = new ImageMetadata([
            'GPSLatitude' => 48.8566,
            'GPSLongitude' => 2.3522,
        ]);

        $gps = $meta->gps();
        $this->assertInstanceOf(GpsCoordinate::class, $gps);
        $this->assertSame(48.8566, $gps->latitude);
        $this->assertSame(2.3522, $gps->longitude);
    }

    public function testGpsReturnsNullWhenMissing(): void
    {
        $meta = new ImageMetadata(['Make' => 'Canon']);

        $this->assertNull($meta->gps());
    }

    public function testGpsReturnsNullWithPartialData(): void
    {
        $meta = new ImageMetadata(['GPSLatitude' => 48.8566]);

        $this->assertNull($meta->gps());
    }

    public function testDateOriginalFromExifString(): void
    {
        $meta = new ImageMetadata([
            'DateTimeOriginal' => '2024:03:15 10:30:45',
        ]);

        $dt = $meta->dateOriginal();
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertSame('2024', $dt->format('Y'));
        $this->assertSame('03', $dt->format('m'));
        $this->assertSame('15', $dt->format('d'));
        $this->assertSame('10:30:45', $dt->format('H:i:s'));
    }

    public function testDateOriginalFromTimestamp(): void
    {
        $ts = 1710500000;
        $meta = new ImageMetadata(['DateTimeOriginal' => $ts]);

        $dt = $meta->dateOriginal();
        $this->assertInstanceOf(DateTimeImmutable::class, $dt);
        $this->assertSame($ts, $dt->getTimestamp());
    }

    public function testDateOriginalReturnsNullWhenMissing(): void
    {
        $meta = new ImageMetadata();
        $this->assertNull($meta->dateOriginal());
    }

    public function testCameraCombinesMakeAndModel(): void
    {
        $meta = new ImageMetadata([
            'Make' => 'Canon',
            'Model' => 'EOS 5D Mark IV',
        ]);

        $this->assertSame('Canon EOS 5D Mark IV', $meta->camera());
    }

    public function testCameraDeduplicatesWhenModelStartsWithMake(): void
    {
        $meta = new ImageMetadata([
            'Make' => 'NIKON',
            'Model' => 'NIKON D850',
        ]);

        $this->assertSame('NIKON D850', $meta->camera());
    }

    public function testCameraWithOnlyMake(): void
    {
        $meta = new ImageMetadata(['Make' => 'Canon']);
        $this->assertSame('Canon', $meta->camera());
    }

    public function testCameraWithOnlyModel(): void
    {
        $meta = new ImageMetadata(['Model' => 'iPhone 15 Pro']);
        $this->assertSame('iPhone 15 Pro', $meta->camera());
    }

    public function testCameraReturnsNullWhenEmpty(): void
    {
        $meta = new ImageMetadata();
        $this->assertNull($meta->camera());
    }

    public function testTitleFromObjectName(): void
    {
        $meta = new ImageMetadata(['ObjectName' => 'Sunset Photo']);
        $this->assertSame('Sunset Photo', $meta->title());
    }

    public function testTitleFromXmpTitle(): void
    {
        $meta = new ImageMetadata(['Title' => 'Mountain View']);
        $this->assertSame('Mountain View', $meta->title());
    }

    public function testTitlePrefersObjectNameOverXmpTitle(): void
    {
        $meta = new ImageMetadata([
            'ObjectName' => 'IPTC Title',
            'Title' => 'XMP Title',
        ]);
        $this->assertSame('IPTC Title', $meta->title());
    }

    public function testTitleReturnsNullWhenEmpty(): void
    {
        $meta = new ImageMetadata();
        $this->assertNull($meta->title());
    }

    public function testDescriptionFromCaptionAbstract(): void
    {
        $meta = new ImageMetadata(['Caption-Abstract' => 'A beautiful sunset']);
        $this->assertSame('A beautiful sunset', $meta->description());
    }

    public function testDescriptionFromXmpDescription(): void
    {
        $meta = new ImageMetadata(['Description' => 'XMP desc']);
        $this->assertSame('XMP desc', $meta->description());
    }

    public function testDescriptionFromImageDescription(): void
    {
        $meta = new ImageMetadata(['ImageDescription' => 'EXIF desc']);
        $this->assertSame('EXIF desc', $meta->description());
    }

    public function testDescriptionReturnsNullWhenEmpty(): void
    {
        $meta = new ImageMetadata();
        $this->assertNull($meta->description());
    }

    public function testMergeCreatesNewInstance(): void
    {
        $meta = new ImageMetadata(['Make' => 'Canon']);
        $merged = $meta->merge(['Model' => 'EOS R5']);

        $this->assertNotSame($meta, $merged);
        $this->assertFalse($meta->has(MetadataField::Model));
        $this->assertTrue($merged->has(MetadataField::Make));
        $this->assertTrue($merged->has(MetadataField::Model));
    }

    public function testMergeOverwritesExistingKeys(): void
    {
        $meta = new ImageMetadata(['Make' => 'Canon']);
        $merged = $meta->merge(['Make' => 'Nikon']);

        $this->assertSame('Canon', $meta->get(MetadataField::Make));
        $this->assertSame('Nikon', $merged->get(MetadataField::Make));
    }
}
