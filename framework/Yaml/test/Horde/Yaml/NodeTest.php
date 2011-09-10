<?php
/**
 * Horde_Yaml_Node test
 *
 * @author  Mike Naberezny <mike@maintainable.com>
 * @license http://www.horde.org/licenses/bsd BSD
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
