<?php
/**
 * All tests for the Nag:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Nag
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Nag_AllTests::main');
}

/**
 * Prepare the test setup.
 *
 * @todo: fix autoloading
 */
require_once dirname(__FILE__) . '/Autoload.php';
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Nag:: package.
 *
 * @category   Horde
 * @package    Nag
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Nag
 */
class Nag_AllTests extends Horde_Test_AllTests
{
}

Nag_AllTests::init('Nag', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Nag_AllTests::main') {
    Nag_AllTests::main();
}
