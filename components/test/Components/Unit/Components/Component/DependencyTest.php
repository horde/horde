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
class Components_Unit_Components_Component_DependencyTest
extends Components_TestCase
{
    public function testRequiredTrue()
    {
        $this->assertTrue(
            $this->_getDependency(array('type' => 'pkg', 'optional' => 'no'))
            ->isRequired()
        );
    }

    public function testRequiredFalse()
    {
        $this->assertFalse(
            $this->_getDependency(array('type' => 'pkg'))
            ->isRequired()
        );
    }

    public function testIsNotAPackage()
    {
        $this->assertFalse(
            $this->_getDependency(array('type' => 'php'))
            ->isPackage()
        );
    }

    public function testIsAPackage()
    {
        $this->assertTrue(
            $this->_getDependency(array('type' => 'pkg'))
            ->isPackage()
        );
    }

    public function testIsNotHorde()
    {
        $this->assertFalse(
            $this->_getDependency(array())
            ->isPackage()
        );
    }

    public function testIsNotHordeSinceOtherChannel()
    {
        $this->assertFalse(
            $this->_getDependency(array('channel' => 'pear.php.net'))
            ->isPackage()
        );
    }

    public function testIsHorde()
    {
        $this->assertFalse(
            $this->_getDependency(array('channel' => 'pear.horde.org'))
            ->isPackage()
        );
    }

    public function testIsPhp()
    {
        $this->assertTrue(
            $this->_getDependency(array('type' => 'php'))
            ->isPhp()
        );
    }

    public function testIsNotPhp()
    {
        $this->assertFalse(
            $this->_getDependency(array('type' => 'pkg'))
            ->isPhp()
        );
    }

    public function testIsExtension()
    {
        $this->assertTrue(
            $this->_getDependency(array('type' => 'ext'))
            ->isExtension()
        );
    }

    public function testIsNotExtension()
    {
        $this->assertFalse(
            $this->_getDependency(array('type' => 'pkg'))
            ->isExtension()
        );
    }

    public function testIsNotPear()
    {
        $this->assertFalse(
            $this->_getDependency(array('type' => 'pkg'))
            ->isPearBase()
        );
    }

    public function testIsPear()
    {
        $this->assertTrue(
            $this->_getDependency(
                array(
                    'type' => 'pkg',
                    'name' => 'PEAR',
                    'channel' => 'pear.php.net'
                )
            )
            ->isPearBase()
        );
    }

    public function testName()
    {
        $this->assertEquals(
            'PEAR',
            $this->_getDependency(array('type' => 'pkg', 'name' => 'PEAR'))
            ->getName()
        );
    }

    public function testChannel()
    {
        $this->assertEquals(
            'pear.php.net',
            $this->_getDependency(
                array('type' => 'pkg', 'channel' => 'pear.php.net')
            )
            ->getChannel()
        );
    }

    public function testKey()
    {
        $this->assertEquals(
            'pear.php.net/PEAR',
            $this->_getDependency(
                array(
                    'type' => 'pkg',
                    'name' => 'PEAR',
                    'channel' => 'pear.php.net'
                )
            )
            ->key()
        );
    }

    public function testChannelOrTypeType()
    {
        $this->assertEquals(
            'PHP Extension',
            $this->_getDependency(array('type' => 'ext'))
            ->channelOrType()
        );
    }

    public function testChannelOrTypeChannel()
    {
        $this->assertEquals(
            'pear.php.net',
            $this->_getDependency(
                array('type' => 'pkg', 'channel' => 'pear.php.net')
            )
            ->channelOrType()
        );
    }

    private function _getDependency($dependency)
    {
        return new Components_Component_Dependency(
            $dependency, $this->getComponentFactory()
        );
    }

    public function testIsRequired()
    {
        $this->lessStrict();
        $comp = $this->getComponent(
            __DIR__ . '/../../../fixture/framework/Install'
        );
        $this->assertTrue(
            $comp->getDependencyList()->{'pear.horde.org/Dependency'}->isRequired()
        );
    }
}
