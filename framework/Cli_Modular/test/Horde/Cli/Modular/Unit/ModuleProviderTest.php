<?php
/**
 * Test the module provider.
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
 * Test the module provider.
 */
class Horde_Cli_Modular_Unit_ModuleProviderTest
extends Horde_Cli_Modular_TestCase
{
    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testMissingPrefix()
    {
        $provider = new Horde_Cli_Modular_ModuleProvider();
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testInvalidModule()
    {
        $provider = new Horde_Cli_Modular_ModuleProvider(
            array('prefix' => 'INVALID')
        );
        $provider->getModule('One')->getUsage('One');
    }

    public function testUsage()
    {
        $provider = new Horde_Cli_Modular_ModuleProvider(
            array(
                'prefix' => 'Horde_Cli_Modular_Stub_Module_',
                'dependencies' => new stdClass,
            )
        );
        $this->assertEquals(
            'Use One', $provider->getModule('One')->getUsage('One')
        );
    }

    public function testDependencies()
    {
        $dependencies = new stdClass;
        $provider = new Horde_Cli_Modular_ModuleProvider(
            array(
                'prefix' => 'Horde_Cli_Modular_Stub_Module_',
                'dependencies' => $dependencies,
            )
        );
        $this->assertSame(
            $dependencies, $provider->getModule('One')->args[0]
        );
    }
}
