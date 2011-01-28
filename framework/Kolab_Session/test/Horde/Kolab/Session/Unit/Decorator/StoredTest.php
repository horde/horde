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
require_once dirname(__FILE__) . '/../../Autoload.php';

/**
 * Test the storing decorator.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Unit_Decorator_StoredTest
extends Horde_Kolab_Session_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setupStorage();
    }

    public function testShutdownSavesExport()
    {
        $this->storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('save')
            ->with(array('export'));
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('export')
            ->will($this->returnValue(array('export')));
        $stored = new Horde_Kolab_Session_Decorator_Stored($session, $this->storage);
        $stored->connect();
        $stored->shutdown();
    }

    /**
     * @expectedException Horde_Kolab_Session_Exception
     */
    public function testImportException()
    {
        $this->storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue(array()));
        $session = $this->getMock('Horde_Kolab_Session');
        $stored = new Horde_Kolab_Session_Decorator_Stored($session, $this->storage);
        $stored->import(array('import'));
    }

    public function testShutdownSavesPurged()
    {
        $this->storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue(array()));
        $this->storage->expects($this->once())
            ->method('save')
            ->with(array());
        $session = $this->getMock('Horde_Kolab_Session');
        $stored = new Horde_Kolab_Session_Decorator_Stored($session, $this->storage);
        $stored->purge();
        $stored->shutdown();
    }

    public function testMethodConnectGetsDelegated()
    {
        $this->storage->expects($this->once())
            ->method('load')
            ->will($this->returnValue(array()));
        $session = $this->getMock('Horde_Kolab_Session');
        $session->expects($this->once())
            ->method('export')
            ->will($this->returnValue(array()));
        $session->expects($this->once())
            ->method('connect')
            ->with(array('password' => 'pass'));
        $stored = new Horde_Kolab_Session_Decorator_Stored($session, $this->storage);
        $stored->connect(array('password' => 'pass'));
    }
}