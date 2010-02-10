<?php
/**
 * Test the cleanup decorator for the server.
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
 * Test the cleanup decorator for the server.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Decorator_CleanTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->server = $this->getMock('Horde_Kolab_Server_Interface');
        $this->cleaner = new Horde_Kolab_Server_Decorator_Clean($this->server);
    }

    public function testMethodGetbaseguidHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('getBaseGuid')
            ->will($this->returnValue('base'));
        $this->assertEquals('base', $this->cleaner->getBaseGuid());
    }

    public function testMethodGetuidHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('getGuid')
            ->will($this->returnValue('guid'));
        $this->assertEquals('guid', $this->cleaner->getGuid());
    }

    public function testMethodConnectguidHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('connectGuid')
            ->with('user', 'pass');
        $this->cleaner->connectGuid('user', 'pass');
    }

    public function testMethodReadHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid')
            ->will($this->returnValue(array()));
        $this->assertEquals(array(), $this->cleaner->read('guid'));
    }

    public function testMethodReadattributesHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('readAttributes')
            ->with('guid', array('a'))
            ->will($this->returnValue(array()));
        $this->assertEquals(
            array(), $this->cleaner->readAttributes('guid', array('a'))
        );
    }

    public function testMethodFindHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $result = $this->getMock('Horde_Kolab_Server_Result_Interface');
        $query = $this->getMock(
            'Horde_Kolab_Server_Query_Element_Interface', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('find')
            ->with($query)
            ->will($this->returnValue($result));
        $this->assertType(
            'Horde_Kolab_Server_Result_Interface',
            $this->cleaner->find($query)
        );
    }

    public function testMethodFindbelowHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $result = $this->getMock('Horde_Kolab_Server_Result_Interface');
        $query = $this->getMock(
            'Horde_Kolab_Server_Query_Element_Interface', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('findBelow')
            ->with($query, 'none')
            ->will($this->returnValue($result));
        $this->assertType(
            'Horde_Kolab_Server_Result_Interface',
            $this->cleaner->findBelow($query, 'none')
        );
    }

    public function testMethodSaveHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->server->expects($this->exactly(1))
            ->method('save')
            ->with($object, array('a' => 'a'));
        $this->cleaner->save($object, array('a' => 'a'));
    }

    public function testMethodAddHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $this->server->expects($this->exactly(1))
            ->method('add')
            ->with($object, array('a' => 'a'));
        $this->cleaner->add($object, array('a' => 'a'));
    }

    public function testMethodDeleteHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('delete')
            ->with('a');
        $this->cleaner->delete('a');
    }

    public function testMethodRenameHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('rename')
            ->with('a', 'b');
        $this->cleaner->rename('a', 'b');
    }

    public function testMethodGetschemaHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('getSchema');
        $this->cleaner->getSchema();
    }

    public function testMethodGetparentguidHasPostconditionThatTheCallWasDelegatedToTheServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('getParentGuid')
            ->will($this->returnValue('parent'));
        $this->assertEquals('parent', $this->cleaner->getParentGuid('child'));
    }

    public function testMethodAddHasPostconditionThatTheGuidOfTheAddedObjectIsRememberedAndDeletedOnDestruction()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->exactly(1))
            ->method('getGuid')
            ->will($this->returnValue('a'));
        $this->server->expects($this->exactly(1))
            ->method('add')
            ->with($object, array('a' => 'a'));
        $this->server->expects($this->exactly(1))
            ->method('delete')
            ->with('a');
        $this->cleaner->add($object, array('a' => 'a'));
        unset($this->cleaner);
    }

    public function testMethodAddHasPostconditionThatTheGuidOfTheAddedObjectIsNotDeletedOnDestructionIfItWasDeletedBefore()
    {
        $object = $this->getMock('Horde_Kolab_Server_Object_Interface');
        $object->expects($this->exactly(1))
            ->method('getGuid')
            ->will($this->returnValue('a'));
        $this->server->expects($this->exactly(1))
            ->method('add')
            ->with($object, array('a' => 'a'));
        $this->server->expects($this->exactly(1))
            ->method('delete')
            ->with('a');
        $this->cleaner->add($object, array('a' => 'a'));
        $this->cleaner->delete('a');
        unset($this->cleaner);
    }


}