<?php
/**
 * Test the Kolab session handler.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/SessionTest.php,v 1.10 2009/01/14 21:46:54 wrobel Exp $
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
 *  We need the base class
 */
require_once 'Horde/Kolab/Test/Server.php';

require_once 'Horde/Kolab/Session.php';

/**
 * Test the Kolab session handler.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/SessionTest.php,v 1.10 2009/01/14 21:46:54 wrobel Exp $
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
class Horde_Kolab_Server_SessionTest extends Horde_Kolab_Test_Server {

    /**
     * Test class construction.
     *
     * @return NULL
     */
    public function testConstructEmpty()
    {
        global $conf;
        $conf['kolab']['imap']['allow_special_users'] = true;

        $session = &Horde_Kolab_Session::singleton();

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

        $session = &new Horde_Kolab_Session();
        $params  = $session->getImapParams();
        if (is_a($params, 'PEAR_Error')) {
            $this->assertEquals('', $params->getMessage());
        }
        $this->assertEquals('example.com', $params['hostspec']);
        $this->assertEquals(200, $params['port']);
    }

    /**
     * Test IMAP server retrieval.
     *
     * @return NULL
     */
    public function testGetServer()
    {
        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $this->assertEquals(1, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));

        $session = &Horde_Kolab_Session::singleton('test',
                                                   array('password' => 'test'));

        $this->assertNoError($session->auth);
        $this->assertEquals('test@example.org', $session->user_mail);

        $params = $session->getImapParams();
        $this->assertNoError($params);
        $this->assertEquals('home.example.org', $params['hostspec']);
        $this->assertEquals(143, $params['port']);
        $this->assertEquals('test@example.org', $session->user_mail);

        $session->shutdown();

        $hs = &Horde_SessionObjects::singleton();

        $recovered_session = &$hs->query('kolab_session');
        $params            = $recovered_session->getImapParams();
        $this->assertNoError($params);
        $this->assertEquals('home.example.org', $params['hostspec']);
        $this->assertEquals(143, $params['port']);
        $this->assertEquals('test@example.org', $session->user_mail);

        $this->assertEquals('https://fb.example.org/freebusy', $session->freebusy_server);
    }

    /**
     * Test retrieving the FreeBusy server for the unauthenticated state.
     *
     * @return NULL
     */
    public function testGetFreeBusyServer()
    {
        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $session = &Horde_Kolab_Session::singleton();
        $this->assertEquals('', $session->freebusy_server);
    }

    /**
     * Test group based login allow implemention.
     *
     * @return NULL
     */
    public function testLoginAllow()
    {
        global $conf;
        $conf['kolab']['server']['allow_group'] = 'group2@example.org';
        $conf['kolab']['server']['deny_group'] = null;

        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserOne());
        $this->assertNoError($result);
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $groups = $this->validGroups();
        foreach ($groups as $group) {
            $result = $server->add($group[0]);
            $this->assertNoError($result);
        }

        $session = &Horde_Kolab_Session::singleton('wrobel',
                                                   array('password' => 'none'),
                                                   true);

        $this->assertNoError($session->auth);
        $this->assertEquals('wrobel@example.org', $session->user_mail);

        $session = &Horde_Kolab_Session::singleton('test',
                                                   array('password' => 'test'),
                                                   true);

        $this->assertError($session->auth, 'You are no member of a group that may login on this server.');
        $this->assertTrue(empty($session->user_mail));
    }

    /**
     * Test group based login deny implemention.
     *
     * @return NULL
     */
    public function testLoginDeny()
    {
        global $conf;
        $conf['kolab']['server']['deny_group'] = 'group2@example.org';
        unset($conf['kolab']['server']['allow_group']);

        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserOne());
        $this->assertNoError($result);
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $groups = $this->validGroups();
        foreach ($groups as $group) {
            $result = $server->add($group[0]);
            $this->assertNoError($result);
        }

        $session = &Horde_Kolab_Session::singleton('test',
                                                   array('password' => 'test'),
                                                   true);

        $this->assertNoError($session->auth);
        $this->assertEquals('test@example.org', $session->user_mail);

        $session = &Horde_Kolab_Session::singleton('wrobel',
                                                   array('password' => 'none'),
                                                   true);

        $this->assertError($session->auth, 'You are member of a group that may not login on this server.');
        $this->assertTrue(empty($session->user_mail));

    }

}
