<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_UuidTest extends PHPUnit_Framework_TestCase
{
    /**
     * test instantiating a normal timer
     */
    public function testUuidLength()
    {
        $uuid = strval(new Horde_Support_Uuid());
        $this->assertEquals(36, strlen($uuid));
    }

    public function testUuidDuplicates()
    {
        $values = array();
        $cnt = 0;

        for ($i = 0; $i < 10000; ++$i) {
            $id = strval(new Horde_Support_Uuid());
            if (isset($values[$id])) {
                $cnt++;
            } else {
                $values[$id] = 1;
            }
        }

        $this->assertEquals(0, $cnt);
    }

}
