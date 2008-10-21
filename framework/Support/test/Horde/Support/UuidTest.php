<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2008 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2008 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_UuidTest extends PHPUnit_Framework_TestCase
{
    /**
     * test instantiating a normal timer
     */
    public function testUuidLength()
    {
        $uuid = (string)new Horde_Support_Uuid;
        $this->assertEquals(36, strlen($uuid));
    }

}
