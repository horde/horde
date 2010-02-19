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
        $this->factory = new Horde_Kolab_Session_Factory_Configuration($config);

        if (!defined('HORDE_BASE')) {
            define('HORDE_BASE', '/nowhere');
        }
    }

    public function testKolabLoginViaUid()
    {
        $auth = new Horde_Auth_Kolab();
        $auth->setSessionFactory($this->factory);
        $this->assertTrue(
            $auth->authenticate('user', array('password' => 'pass'), false)
        );
    }

    public function testKolabLoginViaMail()
    {
        $auth = new Horde_Auth_Kolab();
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
}

class Horde_Auth_Kolab_Dummy extends Horde_Auth_Kolab
{
    public function getId()
    {
        return $this->_credentials['userId'];
    }
}
