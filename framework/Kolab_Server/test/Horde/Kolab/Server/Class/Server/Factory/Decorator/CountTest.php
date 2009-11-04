<?php
/**
 * Test the count decorator server factory.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../../Autoload.php';

/**
 * Test the count decorator server factory.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Factory_Decorator_CountTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->factory = $this->getMock(
            'Horde_Kolab_Server_Factory_Interface'
        );
    }

    public function testMethodGetserverHasResultCountedServer()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $this->assertType('Horde_Kolab_Server_Decorator_Count', $factory->getServer());
    }

    public function testMethodConstructHasParametersFactoryAndMixedLoggerParameter()
    {
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
    }

    public function testMethodGetconnectionfactoryHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnectionFactory');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getConnectionFactory();
    }

    public function testMethodGetserverHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will(
                $this->returnValue(
                    $this->getMock(
                        'Horde_Kolab_Server_Interface'
                    )
                )
            );
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getServer();
    }

    public function testMethodGetconfigurationHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConfiguration');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getConfiguration();
    }

    public function testMethodGetconnectionHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnection');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getConnection();
    }

    public function testMethodGetcompositeHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getComposite');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getComposite();
    }

    public function testMethodGetobjectsHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getObjects');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getObjects();
    }

    public function testMethodGetstructureHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getStructure');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getStructure();
    }

    public function testMethodGetsearchHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSearch');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getSearch();
    }

    public function testMethodGetschemaHasPostconditionThatTheCallWasDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSchema');
        $factory = new Horde_Kolab_Server_Factory_Decorator_Count(
            $this->factory, 'logger'
        );
        $factory->getSchema();
    }
}