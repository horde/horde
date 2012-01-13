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
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Mnemo_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * All tests for the Mnemo:: package.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL). If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category   Horde
 * @package    Mnemo
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/apache
 * @link       http://www.horde.org/apps/mnemo
 */
class Mnemo_AllTests extends Horde_Test_AllTests
{
}

Mnemo_AllTests::init('Mnemo', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Mnemo_AllTests::main') {
    Mnemo_AllTests::main();
}
