<?php
/**
 * Test the group object.
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
 * Test the group object.
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
class Horde_Kolab_Server_GroupTest extends Horde_Kolab_Test_Server
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
     * Add a group object.
     *
     * @return NULL
     */
    private function _addValidGroups()
    {
        $groups = $this->validGroups();
        foreach ($groups as $group) {
            $result = $this->ldap->add($group[0]);
            $this->assertNoError($result);
        }
    }

    /**
     * Test ID generation for a group.
     *
     * @return NULL
     */
    public function testGenerateId()
    {
        $groups = $this->validGroups();
        $this->assertEquals('cn=empty.group@example.org,dc=example,dc=org',
                            $this->ldap->generateUid('Horde_Kolab_Server_Object_group', $groups[0][0]));
    }

    /**
     * Test fetching a group.
     *
     * @return NULL
     */
    public function testFetchGroup()
    {
        $this->_addValidGroups();

        $group = $this->ldap->fetch('cn=empty.group@example.org,dc=example,dc=org');
        $this->assertNoError($group);
        $this->assertEquals('Horde_Kolab_Server_Object_group', get_class($group));
    }

    /**
     * Test fetching a group.
     *
     * @return NULL
     */
    public function testToHash()
    {
        $this->_addValidGroups();

        $group = $this->ldap->fetch('cn=empty.group@example.org,dc=example,dc=org');
        $this->assertNoError($group);

        $hash = $group->toHash();
        $this->assertNoError($hash);
        $this->assertContains('mail', array_keys($hash));
        $this->assertContains('id', array_keys($hash));
        $this->assertContains('visible', array_keys($hash));
        $this->assertEquals('empty.group@example.org', $hash['mail']);
        $this->assertEquals('empty.group@example.org', $hash['id']);
        $this->assertTrue($hash['visible']);
    }

    /**
     * Test listing groups.
     *
     * @return NULL
     */
    public function testListingGroups()
    {
        $this->assertEquals(0, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));
        $this->assertEquals(0,
                            count($this->ldap->search('(&(!(cn=domains))(objectClass=kolabGroupOfNames))',
                                                      array(),
                                                      $this->ldap->getBaseUid())));

        $this->_addValidGroups();

        $this->assertEquals(3, count($GLOBALS['KOLAB_SERVER_TEST_DATA']));
        $this->assertEquals(3,
                            count($this->ldap->search('(&(!(cn=domains))(objectClass=kolabGroupOfNames))',
                                                      array(),
                                                      $this->ldap->getBaseUid())));

        $list = $this->ldap->listObjects('Horde_Kolab_Server_Object_group');
        $this->assertNoError($list);
        $this->assertEquals(3, count($list));
    }

}
