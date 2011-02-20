<?php
/**
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php BSD
 * @category   Horde
 * @package    Horde_Xml_Element
 * @subpackage UnitTests
 */

require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib/Horde/Xml/Element.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib/Horde/Xml/Element/Exception.php';
require_once dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/lib/Horde/Xml/Element/List.php';

class Horde_Xml_Element_IteratorTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->list = new Horde_Xml_Element_IteratorTest_List(
            '<?xml version="1.0" encoding="utf-8"?><list><item>1</item><item>2</item></list>'
        );
    }

    public function testRewind()
    {
        $times = 0;
        foreach ($this->list as $i) {
            ++$times;
        }

        $times2 = 0;
        foreach ($this->list as $i) {
            ++$times2;
        }

        $this->assertEquals($times, $times2, 'List should have the same number of iterations multiple times through');
    }

    public function testCurrent()
    {
        foreach ($this->list as $i) {
            $this->assertInstanceOf('Horde_Xml_Element', $i, 'Each list item should be an instance of Horde_Xml_Element');
            break;
        }
    }

    public function testKey()
    {
        $keys = array();
        foreach ($this->list as $k => $i) {
            $keys[] = $k;
        }
        $this->assertEquals($keys, array(0, 1), 'List should have keys 0 and 1');
    }

    public function testNext()
    {
        $last = null;
        foreach ($this->list as $current) {
            $this->assertFalse($last === $current, 'Iteration should produce a new object each item');
            $last = $current;
        }
    }

}

class Horde_Xml_Element_IteratorTest_List extends Horde_Xml_Element_List
{
    protected function _buildListItemCache()
    {
        $results = array();
        foreach ($this->_element->childNodes as $child) {
            if ($child->localName == 'item') {
                $results[] = $child;
            }
        }

        return $results;
    }

}
