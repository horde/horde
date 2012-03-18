<?php
/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

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

    static public function setUpBeforeClass()
    {
        if (file_exists(__DIR__ . '/conf.php')) {
            include __DIR__ . '/conf.php';
            self::$conf = $conf;
        }
    }
}
