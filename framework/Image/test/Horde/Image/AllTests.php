<?php
/**
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Image
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Image_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Image
 * @subpackage UnitTests
 */
class Horde_Image_AllTests extends Horde_Test_AllTests
{
}

Horde_Image_AllTests::init('Horde_Image', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Image_AllTests::main') {
    Horde_Image_AllTests::main();
}
