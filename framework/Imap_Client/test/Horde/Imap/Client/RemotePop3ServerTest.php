<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Package testing on a remote POP3 server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_RemotePop3ServerTest extends Horde_Test_Case
{
    private $pop3;

    public function setUp()
    {
        $config = self::getConfig('IMAPCLIENT_TEST_CONFIG_POP3');
        if (is_null($config) || empty($config['pop3client']['enabled'])) {
            $this->markTestSkipped('POP3 server test not enabled.');
        }

        if (empty($config['pop3client']['client_config']['username']) ||
            empty($config['pop3client']['client_config']['password'])) {
            $this->markTestSkipped('Remote server authentication not configured.');
        }

        try {
            $config['pop3client']['client_config']['cache'] = array(
                'cacheob' => new Horde_Cache(
                    new Horde_Cache_Storage_Mock(),
                    array('compress' => true)
                )
            );
        } catch (Exception $e) {}

        $this->pop3 = new Horde_Imap_Client_Socket_Pop3($config['pop3client']['client_config']);
        $this->pop3->login();
    }

    public function tearDown()
    {
        unset($this->pop3);
    }

    public function testCommands()
    {
        $test_mbox = 'INBOX';

        $this->pop3->capability();
        if (!$this->pop3->queryCapability('USER')) {
            $this->fail('Capability command failed.');
        }

        $this->pop3->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READONLY);
        $this->pop3->openMailbox($test_mbox, Horde_Imap_Client::OPEN_READWRITE);
        $this->pop3->openMailbox($test_mbox, Horde_Imap_Client::OPEN_AUTO);

        // Listing all mailboxes (flat format).
        $this->pop3->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));

        // All status information for test mailbox.
        $this->pop3->status($test_mbox, Horde_Imap_Client::STATUS_ALL);
    }

}
