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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/Scenario.php';

/**
 * Test the admin object.
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
class Horde_Kolab_Server_Integration_AdminTest extends Horde_Kolab_Server_Integration_Scenario
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
    }

    /**
     * Add an administrator object.
     *
     * @return NULL
     */
    private function _addValidAdmin()
    {
        $this->addToServers($this->provideBasicAdmin());
    }

    /**
     * Test ID generation for an admin.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        $admin = $this->provideBasicAdmin();
        $user  = new Horde_Kolab_Server_Object_Kolab_Administrator($this->server,
                                                                   null, $admin);
        $this->assertEquals(
            'cn=The Administrator,dc=example,dc=org',
            $user->get(Horde_Kolab_Server_Object::ATTRIBUTE_UID)
        );
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
        $this->assertContains(
            'cn=admin,cn=internal,dc=example,dc=org',
            array_keys($GLOBALS['KOLAB_SERVER_TEST_DATA'])
        );

        $administrators = $this->server->getGroups(
            'cn=The Administrator,dc=example,dc=org'
        );
        $admin_group    = $this->server->fetch(
            'cn=admin,cn=internal,dc=example,dc=org'
        );

        $this->assertTrue($admin_group->exists());

        $admin = $this->server->fetch('cn=The Administrator,dc=example,dc=org');
        $this->assertEquals(
            'Horde_Kolab_Server_Object_Kolab_Administrator',
            get_class($admin)
        );
    }

    /**
     * Test listing the admins.
     *
     * @return NULL
     */
    public function testToHash()
    {
        $this->_addValidAdmin();

        $hash = $this->server->fetch(
            'cn=The Administrator,dc=example,dc=org'
        )->toHash();
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

        $entries = $this->server->search(
            '(&(cn=*)(objectClass=inetOrgPerson)(!(uid=manager))(sn=*))'
        );
        $this->assertEquals(1, count($entries));

        $list = $this->server->listObjects(
            'Horde_Kolab_Server_Object_Kolab_Administrator'
        );
        $this->assertEquals(1, count($list));
    }

}
