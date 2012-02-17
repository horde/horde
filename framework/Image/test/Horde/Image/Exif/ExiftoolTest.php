<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Image_Exif_ExiftoolTest extends Horde_Image_Test_Exif_Base
{
    public static function setUpBeforeClass()
    {
        $config = self::getConfig('IMAGE_EXIF_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if ($config && !empty($config['image']['exiftool'])) {
            self::$_exif = new Horde_Image_Exif_Exiftool(array('exiftool' => $config['image']['exiftool']));
            parent::setUpBeforeClass();
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