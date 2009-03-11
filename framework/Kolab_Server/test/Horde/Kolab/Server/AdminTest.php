<?php
/**
 * Test the admin object.
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
 * Test the admin object.
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
class Horde_Kolab_Server_AdminTest extends Horde_Kolab_Test_Server
{

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $this->ldap = $this->prepareEmptyKolabServer();
    }

    /**
     * Add an administrator object.
     *
     * @return NULL
     */
    private function _addValidAdmin()
    {
        $admin  = $this->provideBasicAdmin();
        $result = $this->ldap->add($admin);
        $this->assertNoError($result);
    }

    /**
     * Test ID generation for an admin.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        $admin = $this->provideBasicAdmin();
        $this->assertNoError($admin);
        $uid = $this->ldap->generateUid('Horde_Kolab_Server_Object_administrator', $admin);
        $this->assertNoError($uid);
        $this->assertEquals('cn=The Administrator,dc=example,dc=org', $uid);
    }

    /**
     * Test fetching an admin.
     *
     * @return NULL
     */
    public function testFetchAdmin()
    {
        $this->_addValidAdmin();

        $this->assertEquals(2, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));
        $this->assertContains('cn=admin,cn=internal,dc=example,dc=org',
                              array_keys($GLOBALS['KOLAB_SERVER_TEST_DATA']));

        $administrators = $this->ldap->getGroups('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($administrators);

        $admin_group = $this->ldap->fetch('cn=admin,cn=internal,dc=example,dc=org');
        $this->assertNoError($admin_group);
        $this->assertTrue($admin_group->exists());

        $admin = $this->ldap->fetch('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($admin);
        $this->assertEquals('Horde_Kolab_Server_Object_administrator',
                            get_class($admin));
    }

    /**
     * Test listing the admins.
     *
     * @return NULL
     */
    public function testToHash()
    {
        $this->_addValidAdmin();

        $admin = $this->ldap->fetch('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($admin);

        $hash = $admin->toHash();
        $this->assertNoError($hash);
        $this->assertContains('uid', array_keys($hash));
        $this->assertContains('lnfn', array_keys($hash));
        $this->assertEquals('admin', $hash['uid']);
    }

    /**
     * Test listing admins.
     *
     * @return NULL
     */
    public function testListingGroups()
    {
        $this->_addValidAdmin();

        $entries = $this->ldap->search('(&(cn=*)(objectClass=inetOrgPerson)(!(uid=manager))(sn=*))');
        $this->assertNoError($entries);
        $this->assertEquals(1, count($entries));

        $list = $this->ldap->listObjects('Horde_Kolab_Server_Object_administrator');
        $this->assertNoError($list);
        $this->assertEquals(1, count($list));
    }

}
