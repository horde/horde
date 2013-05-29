<?php
/**
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 */
/**
 * Unit tests for ActiveSync functionality in Core.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @category   Horde
 * @package    Core
 * @subpackage UnitTests
 */
class Horde_Core_ActiveSyncTests extends Horde_Test_Case
{
    protected $_auth;
    protected $_state;
    protected $_mailboxes;
    protected $_special;

    public function setUp()
    {
        $this->_auth = $this->getMockSkipConstructor('Horde_Auth_Auto');
        $this->_state = $this->getMockSkipConstructor('Horde_ActiveSync_State_Sql');
    }

    public function _setUpMailTest()
    {
        $this->_mailboxes = array(
            'INBOX' => array(
                'a' => 40,
                'd' => '/',
                'label' =>'Inbox',
                'level' => 0,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'sent-mail' => array(
                'a'=> 8,
                'd' => '/',
                'label' => 'Sent',
                'level' => 0,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'Draft' => array(
                'a' => 8,
                'd' => '/',
                'label' => 'Drafts',
                'level' => 0,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'spam_folder' => array(
                'a' => 8,
                'd' => '/',
                'label' => 'Spam',
                'level' => 0,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'One' => array(
                'a' => 12,
                'd' => '/',
                'label' => 'One',
                'level' => 0,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'One/Two' => array(
                'a' => 12,
                'd' => '/',
                'label' => 'One/Two',
                'level' => 1,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox')),
            'One/Two/Three' => array(
                'a' => 8,
                'd' => '/',
                'label' => 'One/Two/Three',
                'level' => 2,
                'ob' => $this->getMockSkipConstructor('Horde_Imap_Client_Mailbox'))
        );

        $this->_special = array(
            'composetemplates' => new MockIMPMailbox('Templates'),
            'drafts' => new MockIMPMailbox('Draft'),
            'sent' => new MockIMPMailbox('sent-mail'),
            'spam' => new MockIMPMailbox('Spam'),
            'trash' => new MockIMPMailbox('Trash'),
            'userhook' => array()
        );
    }

    public function testGetFolders()
    {
        $this->_setUpMailTest();
        $connector = new MockConnector();

        $adapter = $this->getMockSkipConstructor('Horde_ActiveSync_Imap_Adapter');
        $adapter->expects($this->once())->method('getMailboxes')->will($this->returnValue($this->_mailboxes));
        $adapter->expects($this->any())->method('getSpecialMailboxes')->will($this->returnValue($this->_special));
        $driver = new Horde_Core_ActiveSync_Driver(array(
            'state' => $this->_state,
            'connector' => $connector,
            'auth' => $this->_auth,
            'imap' => $adapter));
        $folders = $driver->getFolders();
        $have = array(
            'Draft' => false,
            'INBOX' => false,
            'sent-mail' => false,
            'spam_folder' => false);

        // Test the EAS Type of each special folder
        foreach ($folders as $f) {
            // Save the nested folder uids for testing later.
            if ($f->_serverid == 'One') {
                $one = $f;
            } elseif ($f->_serverid == 'One/Two') {
                $two = $f;
            } elseif ($f->_serverid == 'One/Two/Three') {
                $three = $f;
            }

            $have[$f->_serverid] = true;
            switch ($f->serverid) {
            case 'Draft':
                $this->assertEquals(3, $f->type);
                break;
            case 'INBOX':
                $this->assertEquals(2, $f->type);
                break;
            case 'sent-mail':
                $this->assertEquals(5, $f->type);
                break;
            case 'spam_folder':
                $this->assertEquals(12, $f->type);
                break;
            }
        }

        // Make sure we have them all.
        foreach (array('Draft', 'INBOX', 'sent-mail', 'spam_folder', 'One', 'One/Two', 'One/Two/Three') as $test) {
            if (!$have[$test]) {
                $this->fail('Missing ' . $test);
            }
        }

        // Make sure the hierarchy looks right.
        $this->assertEquals($two->serverid, $three->parentid);
        $this->assertEquals($one->serverid, $two->parentid);
        $this->assertEquals(0, $one->parentid);
    }

    public function testFbGeneration()
    {
        $connector = new MockConnector();
        $driver = new Horde_Core_ActiveSync_Driver(array(
            'state' => $this->_state,
            'connector' => $connector,
            'auth' => $this->_auth,
            'imap' => null));

        $fixture = new stdClass();
        $fixture->s = '20130529';
        $fixture->e = '20130628';
        $fixture->b = array(
            '1369850400' => 1369854000,  // 5/29 2:00PM - 3:00PM EDT
            '1370721600' => 1370728800
        );

        // Times requested by the client in a RESOLVERECIPIENTS request.
        $start = new Horde_Date('2013-05-29T03:00:00.000Z'); // 5/28 11:00PM EDT
        $end = new Horde_Date('2013-05-30T03:00:00.000Z'); // 5/29 11:00 PM EDT
        $fb = $driver->buildFbString($fixture, $start, $end);
        $expected = '440000000000000000000000000000220000000000000000';
        $this->assertEquals($expected, $fb);
    }
}

/**
 * Mock Connector. Can't mock it since it contain type hints for objects from
 * other libraries (which causes PHPUnit to have a fit).
 *
 */
class MockConnector extends Horde_Core_ActiveSync_Connector
{
    public function __construct()
    {
    }

    public function horde_listApis()
    {
        return array('mail');
    }

}

/**
 * Mock the IMP_Mailbox class
 *
 * Needs to return the value property
 */
class MockIMPMailbox
{
    protected $_name;

    public function __construct($mbox)
    {
        $this->_name = $mbox;
    }

    public function __get($property)
    {
        switch ($property) {
        case 'value':
            return $this->_name;
        }
    }

}
