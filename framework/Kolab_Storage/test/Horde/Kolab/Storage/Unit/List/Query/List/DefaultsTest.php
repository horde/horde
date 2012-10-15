<?php
/**
 * Test the defaults helper.
 *
 * PHP version 5
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../../../../Autoload.php';

/**
 * Test the defaults helper.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Kolab
 * @package    Kolab_Storage
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link       http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Unit_List_Query_List_DefaultsTest
extends PHPUnit_Framework_TestCase
{
    public function testMarkCompleteIsComplete()
    {
        $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        $defaults->markComplete();
        $this->assertTrue($defaults->isComplete());
    }

    public function testGetDefaults()
    {
        $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        $defaults->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $defaults->rememberDefault('FooC', 'TypeFOOBAR', 'Mr. Foo', false);
        $defaults->rememberDefault('BarA', 'TypeBAR', 'Mr. Bar', false);
        $defaults->rememberDefault('BarC', 'TypeFOOBAR', 'Mr. Bar', false);
        $this->assertEquals(
            array(
                'Mr. Foo' => array(
                    'TypeFOO' => 'FooA',
                    'TypeFOOBAR' => 'FooC',
                ), 
                'Mr. Bar' => array(
                    'TypeBAR' => 'BarA',
                    'TypeFOOBAR' => 'BarC',
                ),
            ),
            $defaults->getDefaults()
        );
    }

    public function testGetPersonalDefaults()
    {
        $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        $defaults->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', true);
        $defaults->rememberDefault('FooC', 'TypeFOOBAR', 'Mr. Foo', true);
        $defaults->rememberDefault('BarA', 'TypeBAR', 'Mr. Bar', false);
        $this->assertEquals(
            array(
                'TypeFOO' => 'FooA',
                'TypeFOOBAR' => 'FooC',
            ),
            $defaults->getPersonalDefaults()
        );
    }

    /**
     * @expectedException Horde_Kolab_Storage_List_Exception
     */
    public function testBailOnDoubleDefault()
    {
        $bail = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        $bail->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $bail->rememberDefault('FooC', 'TypeFOO', 'Mr. Foo', false);
    }

    public function testLogOnDoubleDefault()
    {
        $logger = $this->getMock('Horde_Log_Logger', array('err'));
        $logger->expects($this->once())
            ->method('err')
            ->with('Both folders "FooA" and "FooC" of owner "Mr. Foo" are marked as default folder of type "TypeFOO"!');
        $log = new Horde_Kolab_Storage_List_Query_List_Defaults_Log(
            $logger
        );
        $log->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $log->rememberDefault('FooC', 'TypeFOO', 'Mr. Foo', false);
    }

    public function testDuplicates()
    {
        $logger = $this->getMock('Horde_Log_Logger', array('err'));
        $log = new Horde_Kolab_Storage_List_Query_List_Defaults_Log(
            $logger
        );
        $log->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $log->rememberDefault('FooC', 'TypeFOO', 'Mr. Foo', false);
        $this->assertEquals(
            array(
                'TypeFOO' => array(
                    'Mr. Foo' => array('FooA', 'FooC')
                )
            ),
            $log->getDuplicates()
        );
    }

    public function testTriplicate()
    {
        $logger = $this->getMock('Horde_Log_Logger', array('err'));
        $log = new Horde_Kolab_Storage_List_Query_List_Defaults_Log(
            $logger
        );
        $log->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $log->rememberDefault('FooB', 'TypeFOO', 'Mr. Foo', false);
        $log->rememberDefault('FooC', 'TypeFOO', 'Mr. Foo', false);
        $this->assertEquals(
            array(
                'TypeFOO' => array(
                    'Mr. Foo' => array('FooA', 'FooB', 'FooC')
                )
            ),
            $log->getDuplicates()
        );
    }

    public function testReset()
    {
        $defaults = new Horde_Kolab_Storage_List_Query_List_Defaults_Bail();
        $defaults->rememberDefault('FooA', 'TypeFOO', 'Mr. Foo', false);
        $defaults->rememberDefault('FooC', 'TypeFOOBAR', 'Mr. Foo', false);
        $defaults->rememberDefault('BarA', 'TypeBAR', 'Mr. Bar', false);
        $defaults->rememberDefault('BarC', 'TypeFOOBAR', 'Mr. Bar', false);
        $defaults->reset();
        $this->assertEquals(array(), $defaults->getDefaults());
    }
}