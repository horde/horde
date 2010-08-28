<?php
/**
 * All tests for the Kolab_Resource:: package.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Resource
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Resource
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Kolab_Resource_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Kolab_Resource:: package.
 *
 * @category   Kolab
 * @package    Kolab_Resource
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Kolab_Resource
 */
class Horde_Kolab_Resource_AllTests extends Horde_Test_AllTests
{
}

Horde_Kolab_Resource_AllTests::init('Horde_Kolab_Resource', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Kolab_Resource_AllTests::main') {
    Horde_Kolab_Resource_AllTests::main();
}
