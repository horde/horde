<?php
/**
 * Test the user object.
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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * Test the user object.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Integration_UserTest extends Horde_Kolab_Server_Integration_Scenario
{

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        parent::setUp();

        $this->server = $this->getKolabMockServer();
        $users        = $this->validUsers();
        foreach ($users as $user) {
            $result = $this->server->add($user[0]);
        }
    }

    /**
     * Test ID generation for a user.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        $users = $this->validUsers();
        $user = new Horde_Kolab_Server_Object_Kolab_User($this->server, null, $users[0][0]);
        $this->assertNoError($user);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $user->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID));
    }

    /**
     * Test adding invalid user.
     *
     * @expectedException Horde_Kolab_Server_Exception
     *
     * @return NULL
     */
    public function testAddInvalidUser()
    {
        $user   = $this->provideInvalidUserWithoutGivenName();
        $result = $this->server->add($user);
    }

    /**
     * Test fetching a user.
     *
     * @return NULL
     */
    public function testFetchUser()
    {
        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_User', get_class($user));
        $this->assertEquals('Gunnar Wrobel', $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FNLN));
    }

    /**
     * Test fetching server information.
     *
     * @return NULL
     */
    public function testGetServer()
    {
        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $imap = $user->getServer('imap');
        $this->assertEquals('imap.example.org', $imap);

        $user = $this->server->fetch('cn=Test Test,dc=example,dc=org');
        $imap = $user->getServer('imap');
        $this->assertEquals('home.example.org', $imap);

        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $attr = $user->get(Horde_Kolab_Server_Object_Kolab_User::ATTRIBUTE_FREEBUSYHOST);
        $this->assertEquals('https://fb.example.org/freebusy', $attr);

        $imap = $user->getServer('freebusy');
        $this->assertEquals('https://fb.example.org/freebusy', $imap);
    }

}
