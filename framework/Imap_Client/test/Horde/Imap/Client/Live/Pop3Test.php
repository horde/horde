<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */

/**
 * Package testing on a (live) POP3 server.
 *
 * @author     Michael Slusarz <slusarz@horde.org>
 * @category   Horde
 * @copyright  2013-2014 Horde LLC
 * @ignore
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package    Imap_Client
 * @subpackage UnitTests
 */
class Horde_Imap_Client_Live_Pop3Test extends Horde_Test_Case
{
    static private $error;
    static private $pop3;

    static public function setUpBeforeClass()
    {
        $config = self::getConfig(
            'IMAPCLIENT_TEST_CONFIG_POP3',
            __DIR__ . '/../'
        );

        if (is_null($config) || empty($config['pop3client']['enabled'])) {
            self::$error = 'POP3 server test not enabled.';
            return;
        }

        if (empty($config['pop3client']['client_config']['username']) ||
            empty($config['pop3client']['client_config']['password'])) {
            self::$error = 'Remote server authentication not configured.';
            return;
        }

        try {
            $config['pop3client']['client_config']['cache'] = array(
                'cacheob' => new Horde_Cache(
                    new Horde_Cache_Storage_Mock(),
                    array('compress' => true)
                )
            );
        } catch (Exception $e) {}

        self::$pop3 = new Horde_Imap_Client_Socket_Pop3(
            $config['pop3client']['client_config']
        );
    }

    public function setUp()
    {
        if (self::$error) {
            $this->markTestSkipped(self::$error);
        }
    }

    static public function tearDownAfterClass()
    {
        self::$pop3 = null;
    }

    public function testPreLoginCommands()
    {
        $c = self::$pop3->capability;

        $this->assertInstanceOf(
            'Horde_Imap_Client_Data_Capability',
            $c
        );

        if (!$c->query('USER')) {
            $this->fail('Capability command failed.');
        }
    }

    /**
     * @depends testPreLoginCommands
     */
    public function testLogin()
    {
        /* Throws exception on error, which will prevent all further testing
         * on this server. */
        self::$pop3->login();
    }

    /**
     * @depends testLogin
     */
    public function testPostLoginCapability()
    {
        /* Re-use testPreLoginCommands(). */
        $this->testPreLoginCommands();
    }

    /**
     * @depends testLogin
     */
    public function testOpenMailbox()
    {
        self::$pop3->openMailbox('INBOX', Horde_Imap_Client::OPEN_READONLY);
        self::$pop3->openMailbox('INBOX', Horde_Imap_Client::OPEN_READWRITE);
        self::$pop3->openMailbox('INBOX', Horde_Imap_Client::OPEN_AUTO);
    }

    /**
     * @depends testLogin
     */
    public function testListMailbox()
    {
        // Listing all mailboxes (flat format).
        $l = self::$pop3->listMailboxes(
            '*',
            Horde_Imap_Client::MBOX_ALL,
            array('flat' => true)
        );

        $this->assertEquals(1, count($l));
    }

    /**
     * @depends testLogin
     */
    public function testStatus()
    {
        self::$pop3->status('INBOX', Horde_Imap_Client::STATUS_ALL);
    }

}
