<?php
/**
 * All tests for the Service_Gravatar:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Service_Gravatar
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Service_Gravatar
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Service_Gravatar_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Service_Gravatar:: package.
 *
 * @category   Horde
 * @package    Service_Gravatar
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Service_Gravatar
 */
class Horde_Service_Gravatar_AllTests extends Horde_Test_AllTests
{
}

Horde_Service_Gravatar_AllTests::init('Horde_Service_Gravatar', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Service_Gravatar_AllTests::main') {
    Horde_Service_Gravatar_AllTests::main();
}
