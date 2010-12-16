<?php
/**
 * Test the module wrapper.
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
 * Test the module wrapper.
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
class Horde_Cli_Modular_Unit_ModularTest
extends Horde_Cli_Modular_TestCase
{
    public function testParser()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'modules' => array(
                    'directory' => dirname(__FILE__) . '/../Stub/Module'
                ),
                'provider' => array(
                    'prefix' => 'Horde_Cli_Modular_Stub_Module_'
                ),
            )
        );
        $this->assertInstanceOf('Horde_Argv_Parser', $modular->createParser());
    }

    public function testCustomParser()
    {
        $modular = $this->_getDefault();
        $this->assertInstanceOf('Horde_Test_Stub_Parser', $modular->createParser());
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testMissingModules()
    {
        $modular = new Horde_Cli_Modular();
        $modular->getModules();
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testInvalidModules()
    {
        $modular = new Horde_Cli_Modular(array('modules' => 1.0));
        $modular->getModules();
    }

    public function testObjectModules()
    {
        $modular = new Horde_Cli_Modular(
            array('modules' => new Horde_Cli_Modular_Modules(
                      array(
                          'directory' => dirname(__FILE__) . '/../fixtures/Module'
                      )
                  )
            )
        );
        $this->assertInstanceOf('Horde_Cli_Modular_Modules', $modular->getModules());
    }

    public function testStringModules()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'modules' => 'Horde_Cli_Modular_Stub_Modules'
            )
        );
        $this->assertInstanceOf('Horde_Cli_Modular_Modules', $modular->getModules());
    }

    public function testArrayModules()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'modules' => array(
                    'directory' => dirname(__FILE__) . '/../fixtures/Module'
                ),
            )
        );
        $this->assertInstanceOf('Horde_Cli_Modular_Modules', $modular->getModules());
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testMissingProviders()
    {
        $modular = new Horde_Cli_Modular();
        $modular->getProvider();
    }

    /**
     * @expectedException Horde_Cli_Modular_Exception
     */
    public function testInvalidProviders()
    {
        $modular = new Horde_Cli_Modular(array('provider' => 1.0));
        $modular->getProvider();
    }

    public function testObjectProviders()
    {
        $modular = new Horde_Cli_Modular(
            array('provider' => new Horde_Cli_Modular_ModuleProvider(
                      array('prefix' => 'Test')
                  )
            )
        );
        $this->assertInstanceOf(
            'Horde_Cli_Modular_ModuleProvider', $modular->getProvider()
        );
    }

    public function testStringProviders()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'provider' => 'Horde_Cli_Modular_Stub_Provider'
            )
        );
        $this->assertInstanceOf(
            'Horde_Cli_Modular_ModuleProvider', $modular->getProvider()
        );
    }

    public function testArrayProviders()
    {
        $modular = new Horde_Cli_Modular(
            array(
                'provider' => array(
                    'prefix' => 'Test'
                ),
            )
        );
        $this->assertInstanceOf(
            'Horde_Cli_Modular_ModuleProvider', $modular->getProvider()
        );
    }

    public function testGeneralUsage()
    {
        $modular = $this->_getDefault();
        $this->assertContains(
            'GLOBAL USAGE', $modular->createParser()->formatHelp()
        );
    }

    public function testBaseOption()
    {
        $modular = $this->_getDefault();
        $this->assertContains(
            '--something=SOMETHING', $modular->createParser()->formatHelp()
        );
    }

    public function testGroupTitle()
    {
        $modular = $this->_getDefault();
        $this->assertContains(
            'Test Group Title', $modular->createParser()->formatHelp()
        );
    }

    public function testGroupDescription()
    {
        $modular = $this->_getDefault();
        $this->assertContains(
            'Test Group Description', $modular->createParser()->formatHelp()
        );
    }

    public function testGroupOption()
    {
        $modular = $this->_getDefault();
        $this->assertContains(
            '--group=GROUP', $modular->createParser()->formatHelp()
        );
    }

    private function _getDefault()
    {
        return new Horde_Cli_Modular(
            array(
                'parser' => array(
                    'class' => 'Horde_Test_Stub_Parser',
                    'usage' => 'GLOBAL USAGE'
                ),
                'modules' => array(
                    'directory' => dirname(__FILE__) . '/../Stub/Module'
                ),
                'provider' => array(
                    'prefix' => 'Horde_Cli_Modular_Stub_Module_'
                ),
            )
        );
    }
}
