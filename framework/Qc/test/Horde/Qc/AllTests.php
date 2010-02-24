<?php
/**
 * All tests for the Horde_Qc:: package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Qc
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Qc
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Qc_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Qc
 * @subpackage UnitTests
 */
class Horde_Qc_AllTests extends Horde_Test_AllTests
{
}

Horde_Qc_AllTests::init('Horde_Qc', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Qc_AllTests::main') {
    Horde_Qc_AllTests::main();
}
