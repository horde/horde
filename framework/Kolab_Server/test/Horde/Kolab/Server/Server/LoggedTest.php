<?php
/**
 * Test the LDAP driver.
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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../LdapBase.php';

/**
 * Test the LDAP backend.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Server_LoggedTest extends Horde_Kolab_Server_LdapBase
{
    public function setUp()
    {
        parent::setUp();

        $this->logger = new Horde_Log_Handler_Mock();
        $this->server = $this->getMock('Horde_Kolab_Server');
        $this->logged = new Horde_Kolab_Server_Logged(
            $this->server,
            new Horde_Log_Logger($this->logger)
        );
    }

    public function testMethodConnectguidDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('connectGuid')
            ->with('user', 'pass');
        $this->logged->connectGuid('user', 'pass');
    }

    public function testMethodReadDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('read')
            ->with('guid');
        $this->logged->read('guid');
    }

    public function testMethodReadattributesDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('readAttributes')
            ->with('guid', array('a'));
        $this->logged->readAttributes('guid', array('a'));
    }

    public function testMethodFindDelegatesToServer()
    {
        $query = $this->getMock(
            'Horde_Kolab_Server_Query_Element', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('find')
            ->with($query);
        $this->logged->find($query);
    }

    public function testMethodFindbelowDelegatesToServer()
    {
        $query = $this->getMock(
            'Horde_Kolab_Server_Query_Element', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('findBelow')
            ->with($query, 'none');
        $this->logged->findBelow($query, 'none');
    }

    public function testMethodSaveDelegatesToServer()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('save')
            ->with($object, array('a' => 'a'));
        $this->logged->save($object, array('a' => 'a'));
    }

    public function testMethodAddDelegatesToServer()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $this->server->expects($this->exactly(1))
            ->method('add')
            ->with($object, array('a' => 'a'));
        $this->logged->add($object, array('a' => 'a'));
    }

    public function testMethodDeleteDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('delete')
            ->with('a');
        $this->logged->delete('a');
    }

    public function testMethodRenameDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('rename')
            ->with('a', 'b');
        $this->logged->rename('a', 'b');
    }

    public function testMethodGetschemaDelegatesToServer()
    {
        $this->server->expects($this->exactly(1))
            ->method('getSchema');
        $this->logged->getSchema();
    }

    public function testMethodSaveHasPostconditionThatTheEventWasLogged()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $object->expects($this->once())
            ->method('getGuid')
            ->will($this->returnValue('a'));
        $this->logged->save($object, array('a' => 'a'));
        $this->assertEquals(
            $this->logger->events[0]['message'],
            'The object "a" has been successfully saved!'
        );
    }

    public function testMethodAddHasPostconditionThatTheEventWasLogged()
    {
        $object = $this->getMock(
            'Horde_Kolab_Server_Object', array(), array(), '', false
        );
        $object->expects($this->once())
            ->method('getGuid')
            ->will($this->returnValue('a'));
        $this->logged->add($object, array('a' => 'a'));
        $this->assertEquals(
            $this->logger->events[0]['message'],
            'The object "a" has been successfully added!'
        );
    }

    public function testMethodDeleteHasPostconditionThatTheEventWasLogged()
    {
        $this->logged->delete('a');
        $this->assertEquals(
            $this->logger->events[0]['message'],
            'The object "a" has been successfully deleted!'
        );
    }

    public function testMethodRenameHasPostconditionThatTheEventWasLogged()
    {
        $this->logged->rename('a', 'b');
        $this->assertEquals(
            $this->logger->events[0]['message'],
            'The object "a" has been successfully renamed to "b"!'
        );
    }
}