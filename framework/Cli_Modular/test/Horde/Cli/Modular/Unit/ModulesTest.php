<?php
/**
 * Test the modules handler.
 *
 * PHP version 5
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Cli_Modular
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL
 * @link       http://www.horde.org/components/Horde_Cli_Modular
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the modules handler.
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
            array('directory' => __DIR__ . '/DOES_NOT_EXIST')
        );
    }

    public function testList()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => __DIR__ . '/../fixtures/Module')
        );
        $this->assertEquals(array('One', 'Two'), $modules->listModules());
    }

    public function testExclusion()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array(
                'directory' => __DIR__ . '/../fixtures/Module',
                'exclude' => 'One'
            )
        );
        $this->assertEquals(array('Two'), $modules->listModules());
    }

    public function testIteration()
    {
        $modules = new Horde_Cli_Modular_Modules(
            array('directory' => __DIR__ . '/../fixtures/Module')
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
            array('directory' => __DIR__ . '/../fixtures/Module')
        );
        $this->assertEquals(2, count($modules));
    }
}
