<?php
/**
 * All tests for the Horde_Notification package.
 *
 * @category Horde
 * @package  Notification
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Notification
 */

/**
 * Define the main method
 */
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Horde_Notification_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Horde_Notification
 * @subpackage UnitTests
 */
class Horde_Notification_AllTests extends Horde_Test_AllTests
{
}

Horde_Notification_AllTests::init('Horde_Notification', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Horde_Notification_AllTests::main') {
    Horde_Notification_AllTests::main();
}
