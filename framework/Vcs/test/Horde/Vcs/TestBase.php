<?php
/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @category   Horde
 * @package    Vcs
 * @subpackage UnitTests
 */

class Horde_Vcs_TestBase extends Horde_Test_Case
{
    static $conf;

    public static function setUpBeforeClass()
    {
        self::$conf = self::getConfig('VCS_TEST_CONFIG');
    }
}
