<?php
/**
 * Test the base decorator.
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
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the base decorator.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Unit_Decorator_BaseTest
extends Horde_Kolab_Session_TestCase
{
    public function testMethodConnectGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('connect')
            ->with(array('password' => 'pass'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->connect(array('password' => 'pass'));
    }

    public function testMethodGetidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getId();
    }

    public function testMethodGetmailGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getMail')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getMail();
    }

    public function testMethodGetuidGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getUid')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getUid();
    }

    public function testMethodGetnameGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getName();
    }

    public function testMethodGetimapserverGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getImapServer')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getImapServer();
    }

    public function testMethodGetfreebusyserverGetsDelegated()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('getFreebusyServer')
            ->will($this->returnValue('1'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->getFreebusyServer();
    }

    public function testImport()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('import')
            ->with(array('test'));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->import(array('test'));
    }

    public function testExport()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('export')
            ->will($this->returnValue(array('export')));
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $this->assertEquals(array('export'), $anonymous->export());
    }

    public function testPurge()
    {
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('purge');
        $anonymous = new Horde_Kolab_Session_Decorator_Base(
            $session
        );
        $anonymous->purge();
    }
}