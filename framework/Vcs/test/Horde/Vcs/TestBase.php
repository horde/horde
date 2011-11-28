<?php
/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

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
        if (file_exists(dirname(__FILE__) . '/conf.php')) {
            include dirname(__FILE__) . '/conf.php';
            self::$conf = $conf;
        }
    }
}
