<?php
/**
 * Test the package information parser.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the package information parser.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Pear
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Unit_Rest_DependenciesTest
extends Horde_Pear_TestCase
{
    public function testEmpty()
    {
        $deps = new Horde_Pear_Rest_Dependencies(false);
        $this->assertEquals(array(), $deps->getDependencies());
    }

    public function testSerializedEmpty()
    {
        $deps = new Horde_Pear_Rest_Dependencies('b:0;');
        $this->assertEquals(array(), $deps->getDependencies());
    }

    /**
     * @expectedException Horde_Pear_Exception
     */
    public function testBrokenSerialization()
    {
        $deps = new Horde_Pear_Rest_Dependencies('YYY');
    }

    public function testDependencies()
    {
        $this->assertEquals(
            array(array('name' => 'test', 'type' => 'pkg', 'optional' => 'no')),
            $this->_getDependencies()->getDependencies()
        );
    }

    public function testOptionalDependencies()
    {
        $deps = new Horde_Pear_Rest_Dependencies(
            'a:1:{s:8:"optional";a:1:{s:7:"package";a:1:{s:4:"name";s:4:"test";}}}'
        );
        $this->assertEquals(
            array(array('name' => 'test', 'type' => 'pkg', 'optional' => 'yes')),
            $deps->getDependencies()
        );
    }

    public function testDependencyList()
    {
        $deps = new Horde_Pear_Rest_Dependencies(
            'a:1:{s:8:"optional";a:1:{s:7:"package";a:2:{i:0;a:1:{s:4:"name";s:5:"test2";}i:1;a:1:{s:4:"name";s:5:"test1";}}}}'
        );
        $this->assertEquals(
            array(
                array('name' => 'test2', 'type' => 'pkg', 'optional' => 'yes'),
                array('name' => 'test1', 'type' => 'pkg', 'optional' => 'yes')
            ),
            $deps->getDependencies()
        );
    }

    public function testDependenciesFromStream()
    {
        $this->assertEquals(
            array(array('name' => 'test', 'type' => 'pkg', 'optional' => 'no')),
            $this->_getStreamDependencies()->getDependencies()
        );
    }

    private function _getDependencies()
    {
        return new Horde_Pear_Rest_Dependencies(
            file_get_contents(
                __DIR__ . '/../../fixture/rest/dependencies'
            )
        );
    }

    private function _getStreamDependencies()
    {
        return new Horde_Pear_Rest_Dependencies(
            fopen(__DIR__ . '/../../fixture/rest/dependencies', 'r')
        );
    }
}
