<?php
/**
 * Copyright 2011-2015 Horde LLC (http://www.horde.org/)
 *
 * @author     Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Image_Exif_PhpTest extends Horde_Image_Exif_TestBase
{
    public static function setUpBeforeClass()
    {
        self::$_exif = new Horde_Image_Exif_Php();
    }

    public function setUp()
    {
        if (!extension_loaded('exif')) {
            $this->markTestSkipped('exif extension not available');
        }

        parent::setUp();
    }

}
