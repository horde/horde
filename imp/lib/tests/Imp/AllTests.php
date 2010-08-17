<?php
/**
 * All tests for the Imp:: package.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Imp_AllTests::main');
}

/**
 * Prepare the test setup.
 *
 * @todo: fix autoloading
 */
require_once dirname(__FILE__) . '/Autoload.php';
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Imp:: package.
 *
 * @category   Horde
 * @package    IMP
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Imp
 */
class Imp_AllTests extends Horde_Test_AllTests
{
}

Imp_AllTests::init('Imp', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Imp_AllTests::main') {
    Imp_AllTests::main();
}
