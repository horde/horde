<?php
/**
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * @group      support
 * @category   Horde
 * @package    Support
 * @subpackage UnitTests
 * @copyright  2010 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_RandomidTest extends PHPUnit_Framework_TestCase
{
    public function testRandomidLength()
    {
        $rid = (string)new Horde_Support_Randomid;
        $this->assertEquals(16, strlen($rid));
    }

}
