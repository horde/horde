<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @author     Jan Schneider <jan@horde.org>
 * @category   Horde
 * @package    Vfs
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */
class Horde_Vfs_Test_Base extends Horde_Test_Case
{
    protected static $vfs;

    protected function _listEmpty()
    {
        $this->assertEquals(array(), self::$vfs->listFolder(''));
    }

    public static function tearDownAfterClass()
    {
        self::$vfs = null;
    }
}