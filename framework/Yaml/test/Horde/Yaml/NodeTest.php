<?php
/**
 * Horde_Yaml_Node test
 *
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */

/**
 * @category   Horde
 * @package    Yaml
 * @subpackage UnitTests
 */
class Horde_Yaml_NodeTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorAssignsId()
    {
        $id = 'foo';
        $node = new Horde_Yaml_Node($id);
        $this->assertEquals($id, $node->id);
    }

}
