<?php
/**
 * All tests for the Mnemo:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Mnemo_AllTests::main');
}

/**
 * Prepare the test setup.
 *
 * @todo: fix autoloading
 */
require_once dirname(__FILE__) . '/Autoload.php';
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Mnemo:: package.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Mnemo
 */
class Mnemo_AllTests extends Horde_Test_AllTests
{
}

Mnemo_AllTests::init('Mnemo', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Mnemo_AllTests::main') {
    Mnemo_AllTests::main();
}
