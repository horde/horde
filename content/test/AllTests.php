<?php
/**
 * @package    Rampage_Content
 * @subpackage UnitTests
 */

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Content_AllTests::main');
}

require 'Horde/Autoloader.php';

require dirname(__FILE__) . '/../lib/Types/Manager.php';
require dirname(__FILE__) . '/../lib/Users/Manager.php';
require dirname(__FILE__) . '/../lib/Objects/Manager.php';
require dirname(__FILE__) . '/../lib/Tagger.php';

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
