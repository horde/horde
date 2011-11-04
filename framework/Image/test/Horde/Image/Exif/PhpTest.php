<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Base.php';

/**
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @copyright  2011 Horde LLC (http://www.horde.org/)
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

class Horde_Image_Exif_PhpTest extends Horde_Image_Test_Exif_Base
{
    public static function setUpBeforeClass()
    {
        $config = self::getConfig('IMAGE_EXIF_TEST_CONFIG',
                                  dirname(__FILE__) . '/..');
        if ($config && !empty($config['image']['exiftool'])) {
            self::$_exif = new Horde_Image_Exif_Php();
            parent::setUpBeforeClass();
        }
    }
}