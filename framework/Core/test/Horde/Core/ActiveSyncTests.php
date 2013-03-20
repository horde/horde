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
        foreach ($folders as $f) {
            $have[$f->serverid] = true;
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
        foreach (array('Draft', 'INBOX', 'sent-mail', 'spam_folder') as $test) {
            if (!$have[$test]) {
                $this->fail('Missing ' . $test);
            }
        }
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
