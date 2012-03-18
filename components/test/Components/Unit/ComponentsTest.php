<?php
/**
 * Test the Components entry point.
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
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the Components entry point.
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
class Components_Unit_ComponentsTest
extends Components_TestCase
{
    public function testNoArgument()
    {
        $_SERVER['argv'] = array(
            'horde-components'
        );
        $this->assertContains(
            Components::ERROR_NO_COMPONENT,
            $this->_callStrictComponents()
        );
    }

    public function testHelp()
    {
        $_SERVER['argv'] = array(
            'horde-components',
            '--help'
        );
        $this->assertRegExp(
            '/-h,[ ]*--help[ ]*show this help message and exit/',
            $this->_callStrictComponents()
        );
    }

    public function testWithPackageXml()
    {
        $_SERVER['argv'] = array(
            'horde-components',
            '--list-deps',
            __DIR__ . '/../fixture/framework/Install/package.xml'
        );
        $output = $this->_callUnstrictComponents();
        $this->assertContains(
            '|_Dependency',
            $output
        );
    }

    public function testWithPackageXmlDirectory()
    {
        $_SERVER['argv'] = array(
            'horde-components',
            '--list-deps',
            __DIR__ . '/../fixture/framework/Install'
        );
        $output = $this->_callUnstrictComponents();
        $this->assertContains(
            '|_Dependency',
            $output
        );
    }

    public function testWithinComponent()
    {
        $oldcwd = getcwd();
        chdir(__DIR__ . '/../fixture/framework/Install');
        $_SERVER['argv'] = array(
            'horde-components',
            '--list-deps',
        );
        $output = $this->_callUnstrictComponents();
        chdir($oldcwd);
        $this->assertContains(
            '|_Dependency',
            $output
        );
    }

    public function testWithinComponentNoAction()
    {
        $oldcwd = getcwd();
        chdir(__DIR__ . '/../fixture/framework/Install');
        $_SERVER['argv'] = array(
            'horde-components',
        );
        $output = $this->_callUnstrictComponents();
        chdir($oldcwd);
        $this->assertContains(
            Components::ERROR_NO_ACTION,
            $output
        );
    }
}