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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Session_Integration_SessionTest extends Horde_Kolab_Session_SessionTestCase
{
    /**
     * Setup function.
     *
     * @return NULL.
     */
    protected function setUp()
    {
        $this->markTestIncomplete('Needs to be fixed');
    }

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
    public function testGetSession()
    {
        $this->markTestSkipped();
        $server = &$this->prepareEmptyKolabServer();
        $result = $server->add($this->provideBasicUserTwo());
        $this->assertNoError($result);
        $this->assertEquals(1, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));

        $session = Horde_Kolab_Session::singleton('test',
                                                  array('password' => 'test'));

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

        $this->assertEquals('https://fb.example.org/freebusy', $session->freebusy_server);
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

        $this->markTestSkipped();
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

        $session = Horde_Kolab_Session::singleton('wrobel',
                                                  array('password' => 'none'),
                                                  true);

        $this->assertNoError($session->auth);
        $this->assertEquals('wrobel@example.org', $session->user_mail);

        try {
            $session = Horde_Kolab_Session::singleton('test',
                                                      array('password' => 'test'),
                                                      true);
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertError($e, 'You are no member of a group that may login on this server.');
        }
        // FIXME: Ensure that the session gets overwritten
        //$this->assertTrue(empty($session->user_mail));
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

        $this->markTestSkipped();
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

        $session = Horde_Kolab_Session::singleton('test',
                                                  array('password' => 'test'),
                                                  true);

        $this->assertNoError($session->auth);
        $this->assertEquals('test@example.org', $session->user_mail);

        try {
            $session = Horde_Kolab_Session::singleton('wrobel',
                                                      array('password' => 'none'),
                                                      true);
        } catch (Horde_Kolab_Session_Exception $e) {
            $this->assertError($e, 'You are member of a group that may not login on this server.');
        }
        // FIXME: Ensure that the session gets overwritten
        //$this->assertTrue(empty($session->user_mail));
    }

}
