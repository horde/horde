<?php
/**
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Autoload.php';

/**
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @category   Horde
 * @package    Util
 * @subpackage UnitTests
 */
class Horde_Util_ArrayTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->array = array(
            array('name' => 'foo', 'desc' => 'foo long desc'),
            array('name' => 'aaaa', 'desc' => 'aaa foo long desc'),
            array('name' => 'baby', 'desc' => 'The test data was boring'),
            array('name' => 'zebra', 'desc' => 'Striped armadillos'),
            array('name' => 'umbrage', 'desc' => 'resentment'),
        );
    }

    public function testArraySort()
    {
        Horde_Array::arraySort($this->array);
        $this->assertEquals(
            array(
                1 => array('name' => 'aaaa', 'desc' => 'aaa foo long desc'),
                2 => array('name' => 'baby', 'desc' => 'The test data was boring'),
                0 => array('name' => 'foo', 'desc' => 'foo long desc'),
                4 => array('name' => 'umbrage', 'desc' => 'resentment'),
                3 => array('name' => 'zebra', 'desc' => 'Striped armadillos'),
            ),
            $this->array
        );
    }

    public function testArraySortKey()
    {
        Horde_Array::arraySort($this->array, 'desc');
        $this->assertEquals(
            array(
                1 => array('name' => 'aaaa', 'desc' => 'aaa foo long desc'),
                0 => array('name' => 'foo', 'desc' => 'foo long desc'),
                4 => array('name' => 'umbrage', 'desc' => 'resentment'),
                3 => array('name' => 'zebra', 'desc' => 'Striped armadillos'),
                2 => array('name' => 'baby', 'desc' => 'The test data was boring'),
            ),
            $this->array
        );
    }

    public function testArrayMergeRecursive()
    {
        $this->assertEquals(
            array('one' => 1,
                  'two' => array('two/one' => 1,
                                 'two/two' => 22),
                  'three' => 33,
                  'four' => 4),
            Horde_Array::replaceRecursive(
                array('one' => 1,
                      'two' => array('two/one' => 1,
                                     'two/two' => 2),
                      'three' => 3),
                array('two' => array('two/two' => 22),
                      'three' => 33,
                      'four' => 4)));
    }
}
