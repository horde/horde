<?php
/**
 * Test the Kolab session handler.
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
 * Test the Kolab session handler.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Integration_SessionTest
extends Horde_Kolab_Session_SessionTestCase
{
    /**
     * Test class construction.
     *
     * @return NULL
     */
    public function testConstructEmpty()
    {
        global $conf;
        $conf['kolab']['imap']['allow_special_users'] = true;

        $session = Horde_Kolab_Session::singleton();

        $this->assertEquals('anonymous', $session->user_mail);

        $params = $session->getImapParams();
        $this->assertNoError($params);
        $this->assertEquals('localhost', $params['hostspec']);
        $this->assertEquals(143, $params['port']);
    }

    /**
     * Test old style class construction.
     *
     * @return NULL
     */
    public function testConstructSimple()
    {
        global $conf;
        $conf['kolab']['imap']['server']     = 'example.com';
        $conf['kolab']['imap']['port']       = 200;
        $conf['kolab']['freebusy']['server'] = 'fb.example.com';

        $session = new Horde_Kolab_Session();
        $params  = $session->getImapParams();
        $this->assertEquals('example.com', $params['hostspec']);
        $this->assertEquals(200, $params['port']);
    }

    /**
     * Test IMAP server retrieval.
     *
     * @return NULL
     */
    public function testGetSession()
    {
        $this->markTestSkipped();
        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $this->assertEquals(1, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));

        $session = Horde_Kolab_Session::singleton(
            'test',
            array('password' => 'test')
        );

        $this->assertNoError($session->auth);
        $this->assertEquals('test@example.org', $session->user_mail);

        $params = $session->getImapParams();
        $this->assertNoError($params);
        $this->assertEquals('home.example.org', $params['hostspec']);
        $this->assertEquals(143, $params['port']);
        $this->assertEquals('test@example.org', $session->user_mail);

        $session->shutdown();

        $hs = Horde_SessionObjects::singleton();

        $recovered_session = &$hs->query('kolab_session');
        $params            = $recovered_session->getImapParams();
        $this->assertNoError($params);
        $this->assertEquals('home.example.org', $params['hostspec']);
        $this->assertEquals(143, $params['port']);
        $this->assertEquals('test@example.org', $session->user_mail);

        $this->assertEquals(
            'https://fb.example.org/freebusy', $session->freebusy_server
        );
    }

    /**
     * Test retrieving the FreeBusy server for the unauthenticated state.
     *
     * @return NULL
     */
    public function testGetFreeBusySession()
    {
        $this->markTestSkipped();
        $server = $this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $session = Horde_Kolab_Session::singleton();
        $this->assertEquals('', $session->freebusy_server);
    }


}
