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
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Components
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the Components entry point.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Components
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $output = $this->_callStrictComponents();
        $this->assertContains(
            Components::ERROR_NO_COMPONENT,
            $output
        );
    }

    public function testWithinComponent()
    {
        $this->markTestIncomplete();
        $oldcwd = getcwd();
        chdir(dirname(__FILE__) . '/../fixture/simple');
        $_SERVER['argv'] = array(
            'horde-components',
        );
        $output = $this->_callStrictComponents();
        chdir($oldcwd);
        $this->assertContains(
            Components::ERROR_NO_ACTION,
            $output
        );
    }

    public function testWithPackageXml()
    {
        $_SERVER['argv'] = array(
            'horde-components',
            '--list-deps',
            dirname(__FILE__) . '/../fixture/framework/Install/package.xml'
        );
        $output = $this->_callUnstrictComponents();
    }
}