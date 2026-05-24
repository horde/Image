<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Driver;

use Horde\Image\Color\Color;
use Horde\Image\Drawing\DrawingContext;
use Horde\Image\Drawing\LineCap;
use Horde\Image\Drawing\LineDashPattern;
use Horde\Image\Drawing\ShapeStyle;
use Horde\Image\Drawing\TextStyle;
use Horde\Image\Driver\ImagickDrawingContext;
use Horde\Image\Driver\ImagickDriver;
use Horde\Image\DriverException;
use Horde\Image\Geometry\AffineTransform;
use Horde\Image\Geometry\Point;
use Horde\Image\Geometry\Size;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Imagick;

#[CoversClass(ImagickDrawingContext::class)]
#[RequiresPhpExtension('imagick')]
final class ImagickDrawingContextTest extends TestCase
{
    private ImagickDrawingContext $ctx;
    private Imagick $imagick;

    protected function setUp(): void
    {
        $driver = new ImagickDriver();
        $resource = $driver->create(new Size(200.0, 200.0), Color::rgb(1.0, 1.0, 1.0));
        $this->imagick = $resource->imagick();
        $this->ctx = new ImagickDrawingContext($this->imagick);
    }

    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(DrawingContext::class, $this->ctx);
    }

    public function testSaveRestore(): void
    {
        $result = $this->ctx
            ->save()
            ->setFillColor(Color::rgb(1.0, 0.0, 0.0))
            ->restore();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testRestoreWithoutSaveThrows(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('Unbalanced restore');

        $this->ctx->restore();
    }

    public function testPathAndFill(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(1.0, 0.0, 0.0))
            ->moveTo(10.0, 10.0)
            ->lineTo(50.0, 10.0)
            ->lineTo(50.0, 50.0)
            ->closePath()
            ->fill();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testPathAndStroke(): void
    {
        $result = $this->ctx
            ->setStrokeColor(Color::rgb(0.0, 0.0, 1.0))
            ->setLineWidth(2.0)
            ->moveTo(0.0, 0.0)
            ->lineTo(100.0, 100.0)
            ->stroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testRectFillAndStroke(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.0, 1.0, 0.0))
            ->setStrokeColor(Color::rgb(0.0, 0.0, 0.0))
            ->setLineWidth(1.0)
            ->rect(10.0, 10.0, 80.0, 80.0)
            ->fillAndStroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testCurveTo(): void
    {
        $result = $this->ctx
            ->setStrokeColor(Color::rgb(0.0, 0.0, 0.0))
            ->moveTo(10.0, 100.0)
            ->curveTo(40.0, 10.0, 160.0, 10.0, 190.0, 100.0)
            ->stroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testClip(): void
    {
        $result = $this->ctx
            ->rect(20.0, 20.0, 160.0, 160.0)
            ->clip()
            ->setFillColor(Color::rgb(1.0, 0.0, 0.0))
            ->rect(0.0, 0.0, 200.0, 200.0)
            ->fill();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testSetLineCap(): void
    {
        $result = $this->ctx
            ->setLineCap(LineCap::Round)
            ->setStrokeColor(Color::rgb(0.0, 0.0, 0.0))
            ->setLineWidth(10.0)
            ->moveTo(20.0, 100.0)
            ->lineTo(180.0, 100.0)
            ->stroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testSetDashPattern(): void
    {
        $result = $this->ctx
            ->setDashPattern(new LineDashPattern([10.0, 5.0]))
            ->setStrokeColor(Color::rgb(0.0, 0.0, 0.0))
            ->moveTo(10.0, 10.0)
            ->lineTo(190.0, 10.0)
            ->stroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testTransform(): void
    {
        $result = $this->ctx
            ->save()
            ->transform(AffineTransform::translate(100.0, 100.0))
            ->setFillColor(Color::rgb(1.0, 0.0, 0.0))
            ->rect(-10.0, -10.0, 20.0, 20.0)
            ->fill()
            ->restore();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testCircle(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.0, 1.0, 0.0))
            ->circle(100.0, 100.0, 40.0, ShapeStyle::Fill);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testEllipse(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.0, 0.0, 1.0))
            ->ellipse(100.0, 100.0, 60.0, 30.0, ShapeStyle::StrokeAndFill);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testArc(): void
    {
        $result = $this->ctx
            ->setStrokeColor(Color::rgb(1.0, 0.0, 0.0))
            ->arc(100.0, 100.0, 50.0, 0.0, 270.0, ShapeStyle::Stroke);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testPolygon(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.5, 0.0, 0.5))
            ->polygon([
                new Point(100.0, 20.0),
                new Point(180.0, 100.0),
                new Point(100.0, 180.0),
                new Point(20.0, 100.0),
            ], ShapeStyle::Fill);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testPolygonRequiresThreePoints(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('at least 3 points');

        $this->ctx->polygon([new Point(0.0, 0.0), new Point(1.0, 1.0)], ShapeStyle::Fill);
    }

    public function testPolyline(): void
    {
        $result = $this->ctx
            ->setStrokeColor(Color::rgb(0.0, 0.5, 0.5))
            ->setLineWidth(2.0)
            ->polyline([
                new Point(10.0, 190.0),
                new Point(60.0, 140.0),
                new Point(110.0, 190.0),
                new Point(160.0, 140.0),
            ]);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testPolylineRequiresTwoPoints(): void
    {
        $this->expectException(DriverException::class);
        $this->expectExceptionMessage('at least 2 points');

        $this->ctx->polyline([new Point(0.0, 0.0)]);
    }

    public function testRoundedRect(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::hex('#336699'))
            ->roundedRect(20.0, 20.0, 160.0, 100.0, 12.0, ShapeStyle::Fill);

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testText(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.0, 0.0, 0.0))
            ->text('Hello World', 50.0, 100.0, new TextStyle(size: 20.0));

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testTextWithAngle(): void
    {
        $result = $this->ctx
            ->setFillColor(Color::rgb(0.0, 0.0, 0.0))
            ->text('Rotated', 100.0, 100.0, new TextStyle(size: 14.0, angle: 45.0));

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testFluentChaining(): void
    {
        $result = $this->ctx
            ->save()
            ->setFillColor(Color::rgb(1.0, 0.0, 0.0))
            ->setStrokeColor(Color::rgb(0.0, 0.0, 1.0))
            ->setLineWidth(2.0)
            ->setLineCap(LineCap::Square)
            ->setDashPattern(new LineDashPattern([3.0, 2.0]))
            ->transform(AffineTransform::scale(0.5))
            ->rect(10.0, 10.0, 100.0, 100.0)
            ->fillAndStroke()
            ->restore();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }

    public function testEmptyPathPaintIsNoop(): void
    {
        $result = $this->ctx->stroke();

        self::assertInstanceOf(ImagickDrawingContext::class, $result);
    }
}
