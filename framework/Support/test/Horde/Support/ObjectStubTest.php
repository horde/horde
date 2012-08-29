<?php
/**
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/Autoload.php';

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @license    http://www.horde.org/licenses/bsd
 */
class Horde_Support_ObjectStubTest extends PHPUnit_Framework_TestCase
{
    public function testUndefinedIndex()
    {
        $stub = new Horde_Support_ObjectStub(new stdClass);
        $oldTrackErrors = ini_set('track_errors', 1);
        $php_errormsg = null;
        $this->assertNull($stub->a);
        $this->assertNull($php_errormsg);
        ini_set('track_errors', $oldTrackErrors);
    }
}
