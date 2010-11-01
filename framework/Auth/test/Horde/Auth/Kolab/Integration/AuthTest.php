<?php
/**
 * Kolab authentication tests.
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
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Kolab authentication tests.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category   Horde
 * @package    Auth
 * @subpackage UnitTests
 * @author     Gunnar Wrobel <wrobel@pardus.de>
 * @license    http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link       http://pear.horde.org/index.php?package=Auth
 */
class Horde_Auth_Kolab_Integration_AuthTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->markTestIncomplete('Needs some love');
        /** Provide the mock configuration for the server */
        $config = array();
        $config['ldap']['basedn'] = 'dc=test';
        $config['ldap']['mock']   = true;
        $config['ldap']['data']   = array(
            'dn=user,dc=test' => array(
                'dn' => 'dn=user,dc=test',
                'data' => array(
                    'uid' => array('user'),
                    'mail' => array('user@example.org'),
                    'userPassword' => array('pass'),
                    'objectClass' => array('top', 'kolabInetOrgPerson'),
                )
            )
        );
        //$this->factory = new Horde_Kolab_Session_Factory_Configuration($config);

        if (!defined('HORDE_BASE')) {
            define('HORDE_BASE', '/nowhere');
        }
    }

    public function testKolabLoginViaUid()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->assertTrue(
            $auth->authenticate('user', array('password' => 'pass'), false)
        );
    }

    public function testKolabLoginViaMail()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->assertTrue(
            $auth->authenticate(
                'user@example.org', array('password' => 'pass'), false
            )
        );
    }

    public function testKolabLoginFailureViaUid()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $auth->authenticate('user', array('password' => 'invalid'), false);
        $this->assertEquals(
            Horde_Auth::REASON_BADLOGIN,
            Horde_Auth::getAuthError()
        );
    }

    public function testKolabLoginFailureViaMail()
    {
        $auth = new Horde_Auth_Kolab();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $auth->authenticate(
            'user@example.org', array('password' => 'invalid'), false
        );
        $this->assertEquals(
            Horde_Auth::REASON_BADLOGIN,
            Horde_Auth::getAuthError()
        );
    }

    public function testKolabLoginIdRewrite()
    {
        $auth = new Horde_Auth_Kolab_Dummy();
        throw new PHPUnit_Framework_IncompleteTestError('Horde_Auth_Kolab::setSessionFactory() does not exist.');
        $auth->setSessionFactory($this->factory);
        $this->assertTrue(
            $auth->authenticate('user', array('password' => 'pass'), false)
        );
        $this->assertEquals('user@example.org', $auth->getId());
    }

    /**
     * !!! This test has side effects !!!
     *
     * The Horde_Kolab_Session_Singleton will be set.
     */
    public function testKolabLoginViaSessionSingleton()
    {
        $this->markTestIncomplete('Needs correct kolab session setup.');

        global $conf;

        $conf['kolab']['ldap']['basedn'] = 'dc=test';
        $conf['kolab']['ldap']['mock']   = true;
        $conf['kolab']['ldap']['data']   = array(
            'dn=user,dc=test' => array(
                'dn' => 'dn=user,dc=test',
                'data' => array(
                    'uid' => array('user'),
                    'mail' => array('user@example.org'),
                    'userPassword' => array('pass'),
                    'objectClass' => array('top', 'kolabInetOrgPerson'),
                )
            )
        );

        $auth = new Horde_Auth_Kolab();
        $this->assertTrue(
            $auth->authenticate('user', array('password' => 'pass'), false)
        );
    }

    /* /\** */
    /*  * Test group based login allow implemention. */
    /*  * */
    /*  * @return NULL */
    /*  *\/ */
    /* public function testLoginAllow() */
    /* { */
    /*     global $conf; */
    /*     $conf['kolab']['server']['allow_group'] = 'group2@example.org'; */
    /*     $conf['kolab']['server']['deny_group'] = null; */

    /*     $this->markTestSkipped(); */
    /*     $server = &$this->prepareEmptyKolabServer(); */
    /*     $result = $server->add($this->provideBasicUserOne()); */
    /*     $this->assertNoError($result); */
    /*     $result = $server->add($this->provideBasicUserTwo()); */
    /*     $this->assertNoError($result); */
    /*     $groups = $this->validGroups(); */
    /*     foreach ($groups as $group) { */
    /*         $result = $server->add($group[0]); */
    /*         $this->assertNoError($result); */
    /*     } */

    /*     $session = Horde_Kolab_Session::singleton( */
    /*         'wrobel', */
    /*         array('password' => 'none'), */
    /*         true */
    /*     ); */

    /*     $this->assertNoError($session->auth); */
    /*     $this->assertEquals('wrobel@example.org', $session->user_mail); */

    /*     try { */
    /*         $session = Horde_Kolab_Session::singleton( */
    /*             'test', */
    /*             array('password' => 'test'), */
    /*             true */
    /*         ); */
    /*     } catch (Horde_Kolab_Session_Exception $e) { */
    /*         $this->assertError( */
    /*             $e, 'You are no member of a group that may login on this server.' */
    /*         ); */
    /*     } */
    /*     // FIXME: Ensure that the session gets overwritten */
    /*     //$this->assertTrue(empty($session->user_mail)); */
    /* } */

    /* /\** */
    /*  * Test group based login deny implemention. */
    /*  * */
    /*  * @return NULL */
    /*  *\/ */
    /* public function testLoginDeny() */
    /* { */
    /*     global $conf; */
    /*     $conf['kolab']['server']['deny_group'] = 'group2@example.org'; */
    /*     unset($conf['kolab']['server']['allow_group']); */

    /*     $this->markTestSkipped(); */
    /*     $server = &$this->prepareEmptyKolabServer(); */
    /*     $result = $server->add($this->provideBasicUserOne()); */
    /*     $this->assertNoError($result); */
    /*     $result = $server->add($this->provideBasicUserTwo()); */
    /*     $this->assertNoError($result); */
    /*     $groups = $this->validGroups(); */
    /*     foreach ($groups as $group) { */
    /*         $result = $server->add($group[0]); */
    /*         $this->assertNoError($result); */
    /*     } */

    /*     $session = Horde_Kolab_Session::singleton( */
    /*         'test', */
    /*         array('password' => 'test'), */
    /*         true */
    /*     ); */

    /*     $this->assertNoError($session->auth); */
    /*     $this->assertEquals('test@example.org', $session->user_mail); */

    /*     try { */
    /*         $session = Horde_Kolab_Session::singleton( */
    /*             'wrobel', */
    /*             array('password' => 'none'), */
    /*             true */
    /*         ); */
    /*     } catch (Horde_Kolab_Session_Exception $e) { */
    /*         $this->assertError( */
    /*             $e, 'You are member of a group that may not login on this server.' */
    /*         ); */
    /*     } */
    /*     // FIXME: Ensure that the session gets overwritten */
    /*     //$this->assertTrue(empty($session->user_mail)); */
    /* } */
}

class Horde_Auth_Kolab_Dummy extends Horde_Auth_Kolab
{
    public function getId()
    {
        return $this->_credentials['userId'];
    }
}
