<?php
/**
 * Test the Kolab session handler IMAP implementation.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */

/**
 * Prepare the test setup.
 */
require_once __DIR__ . '/../Autoload.php';

/**
 * Test the Kolab session handler IMAP implementation.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Session
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Session
 */
class Horde_Kolab_Session_Unit_ImapTest extends Horde_Kolab_Session_TestCase
{
    public function testConstruct()
    {
        $session = new Horde_Kolab_Session_Imap(
            new Horde_Kolab_Session_Factory_Imap(), array()
        );
    }

    public function testConstructionParameters()
    {
        $session = new Horde_Kolab_Session_Imap(
            new Horde_Kolab_Session_Factory_Imap(),
            array('params' => 'params')
        );
    }

    public function testMailAddress()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('mail@example.org', $session->getMail());
    }

    public function testUid()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('uid', $session->getUid());
    }

    public function testName()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('name', $session->getName());
    }

    public function testImapHost()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('home.example.org', $session->getImapServer());
    }

    public function testFreeBusyHost()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('https://freebusy.example.org/fb', $session->getFreebusyServer());
    }

    public function testMethodConnectThrowsExceptionIfTheConnectionFailed()
    {
        $session = new Horde_Kolab_Session_Imap(
            new Horde_Kolab_Session_Stub_ImapFactory(1),
            array('users' => array('mail@example.org' => array()))
        );
        try {
            $session->connect('mail@example.org', array('password' => 'pass'));
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertEquals('Login failed!', $e->getMessage());
        }
    }

    public function testMethodConnectThrowsExceptionIfTheCredentialsWereInvalid()
    {
        $session = new Horde_Kolab_Session_Imap(
            new Horde_Kolab_Session_Stub_ImapFactory(100),
            array('users' => array('mail@example.org' => array()))
        );
        try {
            $session->connect('mail@example.org', array('password' => 'pass'));
        } catch (Horde_Kolab_Session_Exception_Badlogin $e) {
            $this->assertEquals('Invalid credentials!', $e->getMessage());
        }
    }

    public function testId()
    {
        $session = $this->_getImapSession();
        $session->connect('mail@example.org', array('password' => ''));
        $this->assertEquals('mail@example.org', $session->getId());
    }

    public function testEmptyGetId()
    {
        $this->assertNull($this->_getImapSession()->getId());
    }

    public function testEmptyGetMail()
    {
        $this->assertNull($this->_getImapSession()->getMail());
    }

    public function testEmptyGetName()
    {
        $this->assertNull($this->_getImapSession()->getName());
    }

    public function testEmptyGetUid()
    {
        $this->assertNull($this->_getImapSession()->getUid());
    }

    public function testEmptyGetFreebusyServer()
    {
        $this->assertNull($this->_getImapSession()->getFreebusyServer());
    }

    public function testEmptyGetImapServer()
    {
        $this->assertNull($this->_getImapSession()->getImapServer());
    }

    public function testImportExport()
    {
        $data = array('test');
        $session = $this->_getImapSession();
        $session->import($data);
        $this->assertEquals($data, $session->export());
    }

    private function _getImapSession()
    {
        return new Horde_Kolab_Session_Imap(
            new Horde_Kolab_Session_Stub_ImapFactory(),
            array(
                'freebusy' => array('url_format' => 'https://%s/fb'),
                'users' => array(
                    'mail@example.org' => array(
                        'user' => array(
                            'mail' => 'mail@example.org',
                            'uid' => 'uid',
                            'name' => 'name',
                        ),
                        'imap' => array(
                            'server' => 'home.example.org',
                        ),
                        'fb' => array(
                            'server' => 'https://freebusy.example.org/fb',
                        ),
                    )
                )
            )
        );
    }

}