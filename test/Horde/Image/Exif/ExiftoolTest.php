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

class ExiftoolTest extends TestBase
{
    public static function setUpBeforeClass(): void
    {
        $config = self::getConfig('IMAGE_EXIF_TEST_CONFIG', __DIR__ . '/..');
        self::$_exif = ($config && !empty($config['image']['exiftool']))
            ? new Horde_Image_Exif_Exiftool(array('exiftool' => $config['image']['exiftool']))
            : null;
    }

    protected function _testKeywordIsString()
    {
        $this->assertInternalType('string', self::$_data['Keywords']);
    }

    protected function _testKeywords()
    {
        $this->assertEquals('bunbun,cadillac mountain,maine', self::$_data['Keywords']);
    }

}
