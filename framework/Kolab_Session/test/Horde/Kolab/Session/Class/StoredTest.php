<?php
/**
 * Test the storing decorator.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the storing decorator.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Class_StoredTest extends Horde_Kolab_Session_SessionTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupStorage();
    }

    public function testMethodDestructHasPostconditionThatTheSessionWasSaved()
    {
        $this->storage->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf('Horde_Kolab_Session'));
        $session = $this->getMock('Horde_Kolab_Session');
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored = null;
    }

    public function testMethodConnectGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->with(array('password' => 'pass'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->connect(array('password' => 'pass'));
    }

    public function testMethodGetidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getId();
    }

    public function testMethodSetidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('setId')
            ->with('1');
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->setId('1');
    }

    public function testMethodGetmailGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getMail();
    }

    public function testMethodGetuidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getUid();
    }

    public function testMethodGetnameGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getName();
    }

    public function testMethodGetimapserverGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getImapServer')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getImapServer();
    }

    public function testMethodGetfreebusyserverGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getFreebusyServer')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getFreebusyServer();
    }

    public function testMethodGetstorageGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getStorage')
            ->will($this->returnValue('1'));
        $stored = new Horde_Kolab_Session_Stored($session, $this->storage);
        $stored->getStorage();
    }


}