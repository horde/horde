<?php
/**
 * @package    Rampage_Content
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Content_AllTests::main');
}

/**
 * Prepare the test setup.
 */
require_once 'Horde/Test/AllTests.php';

/**
 * @package    Rampage_Content
 * @subpackage UnitTests
 */
class Content_AllTests extends Horde_Test_AllTests
{
}

Content_AllTests::init('Content', __FILE__);

if (PHPUnit_MAIN_METHOD == 'Content_AllTests::main') {
    Content_AllTests::main();
}
