<?php
/**
 * Test the configuration handler.
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
require_once __DIR__ . '/../../Autoload.php';

/**
 * Test the configuration handler.
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
class Components_Unit_Components_ConfigsTest
extends Components_TestCase
{
    public function testSetOption()
    {
        $configs = new Components_Configs();
        $configs->setOption('key', 'value');
        $options = $configs->getOptions();
        $this->assertEquals(
            'value',
            $options['key']
        );
    }

    public function testUnshiftArgument()
    {
        $configs = new Components_Configs();
        $configs->unshiftArgument('test');
        $arguments = $configs->getArguments();
        $this->assertEquals(
            'test',
            $arguments[0]
        );
    }

    public function testBOverridesA()
    {
        $configs = new Components_Configs();
        $configs->addConfigurationType($this->_getAConfig());
        $configs->addConfigurationType($this->_getBConfig());
        $config = $configs->getOptions();
        $this->assertEquals('B', $config['a']);
    }

    public function testAOverridesB()
    {
        $configs = new Components_Configs();
        $configs->addConfigurationType($this->_getBConfig());
        $configs->addConfigurationType($this->_getAConfig());
        $config = $configs->getOptions();
        $this->assertEquals('A', $config['a']);
    }

    public function testPushConfig()
    {
        $configs = new Components_Configs();
        $configs->addConfigurationType($this->_getAConfig());
        $configs->unshiftConfigurationType($this->_getBConfig());
        $config = $configs->getOptions();
        $this->assertEquals('A', $config['a']);
    }

    public function testNoNullOverride()
    {
        $configs = new Components_Configs();
        $configs->addConfigurationType($this->_getAConfig());
        $configs->addConfigurationType($this->_getNullConfig());
        $config = $configs->getOptions();
        $this->assertEquals('A', $config['a']);
    }

    private function _getAConfig()
    {
        return new Components_Config_File(
            __DIR__ . '/../../fixture/config/a.php'
        );
    }

    private function _getBConfig()
    {
        return new Components_Config_File(
            __DIR__ . '/../../fixture/config/b.php'
        );
    }

    private function _getNullConfig()
    {
        return new Components_Config_File(
            __DIR__ . '/../../fixture/config/null.php'
        );
    }
}