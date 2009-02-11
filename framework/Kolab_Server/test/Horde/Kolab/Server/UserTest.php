<?php
/**
 * Test the user object.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/UserTest.php,v 1.5 2009/01/06 17:49:27 jan Exp $
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

/**
 * Test the user object.
 *
 * $Horde: framework/Kolab_Server/test/Horde/Kolab/Server/UserTest.php,v 1.5 2009/01/06 17:49:27 jan Exp $
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
class Horde_Kolab_Server_UserTest extends Horde_Kolab_Test_Server {

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $this->server = $this->prepareEmptyKolabServer();
        $users        = $this->validUsers();
        foreach ($users as $user) {
            $result = $this->server->add($user[0]);
            $this->assertNoError($result);
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
        $this->assertEquals('Gunnar Wrobel',
                            Horde_Kolab_Server_Object_user::generateId($users[0][0]));

        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org',
                            $this->server->generateUid(KOLAB_OBJECT_USER,
                                                       $users[0][0]));
    }

    /**
     * Test adding invalid user.
     *
     * @return NULL
     */
    public function testAddInvalidUser()
    {
        $user = $this->provideInvalidUserWithoutGivenName();

        $result = $this->server->add($user);

        $this->assertError($result,
                           'Adding object failed: Either the last name or the given name is missing!');
    }

    /**
     * Test fetching a user.
     *
     * @return NULL
     */
    public function testFetchUser()
    {
        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($user);
        $this->assertEquals('Horde_Kolab_Server_Object_user', get_class($user));
    }

    /**
     * Test fetching server information.
     *
     * @return NULL
     */
    public function testGetServer()
    {
        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($user);
        $imap = $user->getServer('imap');
        $this->assertEquals('imap.example.org', $imap);

        $user = $this->server->fetch('cn=Test Test,dc=example,dc=org');
        $imap = $user->getServer('imap');
        $this->assertEquals('home.example.org', $imap);

        $user = $this->server->fetch('cn=Gunnar Wrobel,dc=example,dc=org');
        $attr = $user->get(KOLAB_ATTR_FREEBUSYHOST);
        if (is_a($attr, 'PEAR_Error')) {
            $this->assertEquals('', $attr->getMessage());
        }
        $this->assertEquals('https://fb.example.org/freebusy', $attr);

        $imap = $user->getServer('freebusy');
        $this->assertEquals('https://fb.example.org/freebusy', $imap);

    }

}
