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
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * Test the user object.
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
class Horde_Kolab_Server_UserTest extends Horde_Kolab_Test_Server
{

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
                            $this->server->generateUid('Horde_Kolab_Server_Object_user',
                                                       $users[0][0]));
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
        $this->assertEquals('Horde_Kolab_Server_Object_user', get_class($user));
        $this->assertEquals('Gunnar Wrobel', $user->get(Horde_Kolab_Server_Object::ATTRIBUTE_FNLN));
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
        $attr = $user->get(Horde_Kolab_Server_Object::ATTRIBUTE_FREEBUSYHOST);
        if (is_a($attr, 'PEAR_Error')) {
            $this->assertEquals('', $attr->getMessage());
        }
        $this->assertEquals('https://fb.example.org/freebusy', $attr);

        $imap = $user->getServer('freebusy');
        $this->assertEquals('https://fb.example.org/freebusy', $imap);
    }

}
