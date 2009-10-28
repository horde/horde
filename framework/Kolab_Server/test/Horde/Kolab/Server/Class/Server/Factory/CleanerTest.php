<?php
/**
 * Test the cleaner server factory.
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
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the cleaner server factory.
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
class Horde_Kolab_Server_Class_Server_Factory_CleanerTest
extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->factory = $this->getMock('Horde_Kolab_Server_Factory');
    }

    public function testMethodGetserverHasResultCleanerServerIfACleanedWasProvidedInTheConfiguration()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Server')));
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array('cleanup' => true)
        );
        $this->assertType('Horde_Kolab_Server_Cleaner', $factory->getServer());
    }

    public function testMethodConstructHasParametersFactory()
    {
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory
        );
    }

    public function testMethodGetconnectionfactoryGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnectionFactory');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getConnectionFactory();
    }

    public function testMethodGetserverGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue($this->getMock('Horde_Kolab_Server')));
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getServer();
    }

    public function testMethodGetconfigurationGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConfiguration');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getConfiguration();
    }

    public function testMethodGetconnectionGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getConnection');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getConnection();
    }

    public function testMethodGetcompositeGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getComposite');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getComposite();
    }

    public function testMethodGetobjectsGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getObjects');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getObjects();
    }

    public function testMethodGetstructureGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getStructure');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getStructure();
    }

    public function testMethodGetsearchGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSearch');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getSearch();
    }

    public function testMethodGetschemaGetsDelegated()
    {
        $this->factory->expects($this->once())
            ->method('getSchema');
        $factory = new Horde_Kolab_Server_Factory_Cleaner(
            $this->factory, array()
        );
        $factory->getSchema();
    }
}