<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  1999-2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_UuidTest extends PHPUnit_Framework_TestCase
{
    public function testLength()
    {
        $this->assertEquals(36, strlen(new Horde_Support_Uuid()));
    }

    public function testDuplicates()
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
