<?php
/**
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Image_Exif_ExiftoolTest extends Horde_Image_Exif_TestBase
{
    public static function setUpBeforeClass()
    {
        $config = self::getConfig('IMAGE_EXIF_TEST_CONFIG', __DIR__ . '/..');
        if ($config && !empty($config['image']['exiftool'])) {
            self::$_exif = new Horde_Image_Exif_Exiftool(array('exiftool' => $config['image']['exiftool']));
        }
    }

    public function setUp()
    {
        if (self::$_exif === null) {
            $this->markTestSkipped('No exiftool configuration');
        }
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