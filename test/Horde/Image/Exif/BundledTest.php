<?php
/**
 * Copyright 2011-2017 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
namespace Horde\Image\Exif;
use Horde_Image_Exif_TestBase as TestBase;
use \Horde_Image_Exif_Bundled;

class BundledTest extends TestBase
{
    public static function setUpBeforeClass(): void
    {
        self::$_exif = new Horde_Image_Exif_Bundled();
    }
}