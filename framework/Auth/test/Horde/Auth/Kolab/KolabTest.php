<?php
/**
 * Kolab authentication tests.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabTest.php,v 1.4 2009/04/01 07:59:47 wrobel Exp $
 *
 * PHP version 5
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */

/**
 * Prepare the test setup.
 */
require_once 'Autoload.php';

/**
 * Kolab authentication tests.
 *
 * $Horde: framework/Auth/tests/Horde/Auth/KolabTest.php,v 1.4 2009/04/01 07:59:47 wrobel Exp $
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
class Horde_Auth_Kolab_KolabTest extends Horde_Kolab_Server_Integration_Scenario
{
    /**
     * Test loggin in after a user has been added.
     *
     * @return NULL
     */
    public function testLogin()
    {
        /** Create the test base */
        $world = &$this->prepareBasicSetup();
        $server = $world['server'];
        $auth = $world['auth'];

        /** Ensure we always use the test server */
        $GLOBALS['conf']['kolab']['server']['driver'] = 'test';

        $uid = $server->uidForIdOrMail('wrobel@example.org');
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $result = $auth->authenticate('wrobel@example.org',
                                      array('password' => 'none'));
        $this->assertNoError($result);
        $this->assertTrue($result);

        $session = Horde_Kolab_Session::singleton();
        $this->assertNoError($session->user_mail);
        $this->assertEquals('wrobel@example.org', $session->user_mail);

        $result = $auth->authenticate('wrobel@example.org',
                                      array('password' => 'invalid'));
        $this->assertFalse($result);

        /** Ensure we don't use a connection from older tests */
        $server->unbind();

        $result = $auth->authenticate('wrobel',
                                      array('password' => 'invalid'));
        $this->assertNoError($result);
        $this->assertFalse($result);

        /** Ensure we don't use a connection from older tests */
        $server->unbind();
        $result = $auth->authenticate('wrobel',
                                      array('password' => 'none'));
        $this->assertNoError($result);
        $this->assertTrue($result);

        $session = Horde_Kolab_Session::singleton();
        $this->assertNoError($session->user_mail);
        $this->assertEquals('wrobel@example.org', $session->user_mail);

        $this->assertEquals('wrobel@example.org', Horde_Auth::getAuth());
    }
}