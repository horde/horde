<?php
/**
 * Test the dependency list.
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../Autoload.php';

/**
 * Test the dependency list.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Components
 */
class Components_Unit_Components_Component_DependencyListTest
extends Components_TestCase
{
    public function testDependencyList()
    {
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->assertInstanceOf(
            'Components_Component_DependencyList',
            $comp->getDependencyList()
        );
    }

    public function testDependencyListIterator()
    {
        $this->lessStrict();
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $list = $comp->getDependencyList();
        foreach ($list as $element) {
            $this->assertInstanceOf('Components_Component_Dependency', $element);
        }
    }

    public function testDependencyNames()
    {
        $this->lessStrict();
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $list = $comp->getDependencyList();
        $names = array();
        foreach ($list as $element) {
            $names[] = $element->getName();
        }
        $this->assertEquals(array('', 'PEAR', 'Dependency'), $names);
    }

    public function testAllChannels()
    {
        $this->lessStrict();
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->assertEquals(
            array('pear.php.net', 'pear.horde.org'),
            $comp->getDependencyList()->listAllChannels()
        );
    }

    public function testGetDependency()
    {
        $this->lessStrict();
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->assertInstanceOf(
            'Components_Component_Dependency',
            $comp->getDependencyList()->{'pear.horde.org/Dependency'}
        );
    }


}