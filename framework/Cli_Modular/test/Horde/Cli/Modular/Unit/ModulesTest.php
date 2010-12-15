<?php
/**
 * Test the modules handler.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the modules handler.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Kolab
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Cli_Modular
 */
class Horde_Cli_Modular_Unit_ModulesTest
extends Horde_Cli_Modular_TestCase
{

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testMissingDirectory()
    {
        $modules = new Horde_Cli_Modular_Modules();
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testInvalidDirectory()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => dirname(__FILE__) . '/DOES_NOT_EXIST')
        );
    }

    public function testList()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => dirname(__FILE__) . '/../fixtures/Module')
        );
        $this->assertEquals(array('One', 'Two'), $modules->listModules());
    }

    public function testExclusion()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array(
                'directory' => dirname(__FILE__) . '/../fixtures/Module',
                'exclude' => 'One'
            )
        );
        $this->assertEquals(array('Two'), $modules->listModules());
    }

    public function testIteration()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => dirname(__FILE__) . '/../fixtures/Module')
        );
        $result = array();
        foreach ($modules as $name => $module) {
            $result[] = $module;
        }
        $this->assertEquals(array('One', 'Two'), $result);
    }

    public function testCount()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => dirname(__FILE__) . '/../fixtures/Module')
        );
        $this->assertEquals(2, count($modules));
    }
}
