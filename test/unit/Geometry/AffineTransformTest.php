<?php

declare(strict_types=1);

namespace Horde\Image\Test\Unit\Geometry;

use Horde\Image\Geometry\AffineTransform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AffineTransform::class)]
final class AffineTransformTest extends TestCase
{
    public function testIdentity(): void
    {
        $t = AffineTransform::identity();

        self::assertSame(1.0, $t->a);
        self::assertSame(0.0, $t->b);
        self::assertSame(0.0, $t->c);
        self::assertSame(1.0, $t->d);
        self::assertSame(0.0, $t->e);
        self::assertSame(0.0, $t->f);
    }

    public function testTranslate(): void
    {
        $t = AffineTransform::translate(10.0, 20.0);

        self::assertSame(1.0, $t->a);
        self::assertSame(0.0, $t->b);
        self::assertSame(0.0, $t->c);
        self::assertSame(1.0, $t->d);
        self::assertSame(10.0, $t->e);
        self::assertSame(20.0, $t->f);
    }

    public function testScale(): void
    {
        $t = AffineTransform::scale(2.0, 3.0);

        self::assertSame(2.0, $t->a);
        self::assertSame(0.0, $t->b);
        self::assertSame(0.0, $t->c);
        self::assertSame(3.0, $t->d);
        self::assertSame(0.0, $t->e);
        self::assertSame(0.0, $t->f);
    }

    public function testScaleUniform(): void
    {
        $t = AffineTransform::scale(2.0);

        self::assertSame(2.0, $t->a);
        self::assertSame(2.0, $t->d);
    }

    public function testRotate90(): void
    {
        $t = AffineTransform::rotate(90.0);

        self::assertEqualsWithDelta(0.0, $t->a, 1e-10);
        self::assertEqualsWithDelta(1.0, $t->b, 1e-10);
        self::assertEqualsWithDelta(-1.0, $t->c, 1e-10);
        self::assertEqualsWithDelta(0.0, $t->d, 1e-10);
    }

    public function testRotate180(): void
    {
        $t = AffineTransform::rotate(180.0);

        self::assertEqualsWithDelta(-1.0, $t->a, 1e-10);
        self::assertEqualsWithDelta(0.0, $t->b, 1e-10);
        self::assertEqualsWithDelta(0.0, $t->c, 1e-10);
        self::assertEqualsWithDelta(-1.0, $t->d, 1e-10);
    }

    public function testSkewX(): void
    {
        $t = AffineTransform::skewX(45.0);

        self::assertSame(1.0, $t->a);
        self::assertSame(0.0, $t->b);
        self::assertEqualsWithDelta(1.0, $t->c, 1e-10);
        self::assertSame(1.0, $t->d);
    }

    public function testSkewY(): void
    {
        $t = AffineTransform::skewY(45.0);

        self::assertSame(1.0, $t->a);
        self::assertEqualsWithDelta(1.0, $t->b, 1e-10);
        self::assertSame(0.0, $t->c);
        self::assertSame(1.0, $t->d);
    }

    public function testMultiplyIdentity(): void
    {
        $t = AffineTransform::translate(5.0, 10.0);
        $result = $t->multiply(AffineTransform::identity());

        self::assertSame($t->a, $result->a);
        self::assertSame($t->e, $result->e);
        self::assertSame($t->f, $result->f);
    }

    public function testMultiplyTranslations(): void
    {
        $t1 = AffineTransform::translate(10.0, 0.0);
        $t2 = AffineTransform::translate(0.0, 20.0);
        $result = $t1->multiply($t2);

        self::assertEqualsWithDelta(10.0, $result->e, 1e-10);
        self::assertEqualsWithDelta(20.0, $result->f, 1e-10);
    }

    public function testMultiplyScaleAndTranslate(): void
    {
        $scale = AffineTransform::scale(2.0);
        $translate = AffineTransform::translate(10.0, 5.0);
        $result = $scale->multiply($translate);

        // Scale then translate: translation is not scaled
        self::assertSame(2.0, $result->a);
        self::assertSame(2.0, $result->d);
        self::assertEqualsWithDelta(10.0, $result->e, 1e-10);
        self::assertEqualsWithDelta(5.0, $result->f, 1e-10);
    }
}
