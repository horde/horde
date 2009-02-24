<?php
/**
 * Test the test driver.
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
 * Test the test backend.
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
class Horde_Kolab_Server_testTest extends Horde_Kolab_Test_Server
{

    /**
     * Set up testing.
     *
     * @return NULL
     */
    protected function setUp()
    {
        $this->ldap = &$this->prepareBasicServer();
    }

    /**
     * Test search base.
     *
     * @return NULL
     */
    public function testSearchBase()
    {
        $result = $this->ldap->search('(objectClass=top)', array('objectClass'));
        $this->assertEquals(12, count($result));
      
        $result = $this->ldap->search('(objectClass=top)',
                                      array('objectClass'),
                                      'cn=internal,dc=example,dc=org');
        $this->assertNoError($result);
        $this->assertEquals(3, count($result));
    }

    /**
     * Test sorting.
     *
     * @return NULL
     */
    public function testSorting()
    {
        $result = $this->ldap->search('(mail=*)', array('mail'));
        $this->assertNoError($result);
        $this->assertEquals(5, count($result));
        $this->ldap->sort($result, 'mail');
        $this->assertEquals('address@example.org', $result[0]['data']['mail'][0]);
        $this->assertEquals('wrobel@example.org',
                            $result[count($result) - 1]['data']['mail'][0]);
    }

    /**
     * Test listing objects.
     *
     * @return NULL
     */
    public function testListObjects()
    {
        $filter     = '(&(objectClass=kolabInetOrgPerson)(uid=*)(mail=*)(sn=*))';
        $attributes = array(
            KOLAB_ATTR_SN,
            KOLAB_ATTR_CN,
            KOLAB_ATTR_UID,
            KOLAB_ATTR_MAIL,
            KOLAB_ATTR_DELETED,
        );

        $sort   = KOLAB_ATTR_SN;
        $result = $this->ldap->search($filter);
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));

        $result = $this->ldap->listObjects('Horde_Kolab_Server_Object_user');
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals('Horde_Kolab_Server_Object_user', get_class($result[0]));

        $result = $this->ldap->listObjects('Horde_Kolab_Server_Object_sharedfolder');
        $this->assertNoError($result);
        $this->assertEquals(1, count($result));
        $this->assertEquals('Horde_Kolab_Server_Object_sharedfolder', get_class($result[0]));
    }

    /**
     * Test handling of object classes.
     *
     * @return NULL
     */
    public function testGetObjectClasses()
    {
        $classes = $this->ldap->getObjectClasses('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($classes);
        $this->assertContains('top', $classes);
        $this->assertContains('kolabinetorgperson', $classes);
        $this->assertContains('hordeperson', $classes);

        try {
            $classes = $this->ldap->getObjectClasses('cn=DOES NOT EXIST,dc=example,dc=org');
        } catch (Horde_Kolab_Server_Exception $classes) {
        }
        $this->assertError($classes,
                           'LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object');

        $classes = $this->ldap->getObjectClasses('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($classes);
        $this->assertContains('kolabinetorgperson', $classes);
    }

    /**
     * Test handling of object types.
     *
     * @return NULL
     */
    public function testDetermineType()
    {
        $type = $this->ldap->determineType('cn=empty.group@example.org,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_group', $type);

        $type = $this->ldap->determineType('cn=shared@example.org,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_sharedfolder', $type);

        $type = $this->ldap->determineType('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_administrator', $type);

        $type = $this->ldap->determineType('cn=Main Tainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_maintainer', $type);

        $type = $this->ldap->determineType('cn=Domain Maintainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_domainmaintainer', $type);

        $type = $this->ldap->determineType('cn=Test Address,cn=external,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_address', $type);

        $type = $this->ldap->determineType('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_user', $type);
    }

    /**
     * Test retrieving a primary mail for a mail or id.
     *
     * @return NULL
     */
    public function testMailForIdOrMail()
    {
        $mail = $this->ldap->mailForIdOrMail('wrobel');
        $this->assertEquals('wrobel@example.org', $mail);

        $mail = $this->ldap->mailForIdOrMail('wrobel@example.org');
        $this->assertNoError($mail);
        $this->assertEquals('wrobel@example.org', $mail);

        $mail = $this->ldap->mailForIdOrMail('DOES NOT EXIST');
        $this->assertNoError($mail);
        $this->assertSame(null, $mail);
    }

    /**
     * Test retrieving a UID for a mail or id.
     *
     * @return NULL
     */
    public function testUidForIdOrMail()
    {
        $uid = $this->ldap->uidForIdOrMail('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMail('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMail('DOES NOT EXIST');
        $this->assertNoError($uid);
        $this->assertSame(false, $uid);
    }

    /**
     * Test retrieving a UID for a mail or id.
     *
     * @return NULL
     */
    public function testUidForMailOrIdOrAlias()
    {
        $uid = $this->ldap->uidForIdOrMailOrAlias('g.wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('DOES NOT EXIST');
        $this->assertNoError($uid);
        $this->assertSame(false, $uid);
    }

    /**
     * Test retrieving all addresses for a mail or id.
     *
     * @return NULL
     */
    public function testAddrsForIdOrMail()
    {
        $addrs = $this->ldap->addrsForIdOrMail('wrobel');

        $testuser = $this->ldap->fetch('cn=Test Test,dc=example,dc=org');
        $this->assertNoError($testuser);
        $this->assertContains('wrobel@example.org',
                              $testuser->get(KOLAB_ATTR_KOLABDELEGATE, false));

        $this->assertNoError($addrs);
        $this->assertContains('wrobel@example.org', $addrs);
        $this->assertContains('test@example.org', $addrs);
        $this->assertContains('t.test@example.org', $addrs);
        $this->assertContains('g.wrobel@example.org', $addrs);
        $this->assertContains('gunnar@example.org', $addrs);

        $addrs = $this->ldap->addrsForIdOrMail('test@example.org');
        $this->assertNoError($addrs);
        $this->assertContains('test@example.org', $addrs);
        $this->assertContains('t.test@example.org', $addrs);
    }

    /**
     * Test retrieving a UID for a primary mail.
     *
     * @return NULL
     */
    public function testUidForMailAddress()
    {
        $uid = $this->ldap->uidForIdOrMailOrAlias('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('test@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Test Test,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('gunnar@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForIdOrMailOrAlias('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);
    }

    /**
     * Test retrieving a UID for an attribute.
     *
     * @return NULL
     */
    public function testUidForAttr()
    {
        $uid = $this->ldap->uidForSearch(array('AND' => array(array('field' => 'alias',
                                                                    'op' => '=',
                                                                    'val' => 'g.wrobel@example.org'))));
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);
    }

    /**
     * Test group membership testing.
     *
     * @return NULL
     */
    public function testMemberOfGroupAddress()
    {
        $uid = $this->ldap->uidForIdOrMailOrAlias('g.wrobel@example.org');
        $this->assertNoError($uid);
        $member = $this->ldap->memberOfGroupAddress($uid, 'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $this->ldap->memberOfGroupAddress(
            $this->ldap->uidForIdOrMailOrAlias('test@example.org'),
            'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $this->ldap->memberOfGroupAddress(
            $this->ldap->uidForIdOrMailOrAlias('somebody@example.org'),
            'group@example.org');
        $this->assertNoError($member);
        $this->assertFalse($member);
    }

    /**
     * Test group fetching.
     *
     * @return NULL
     */
    public function testGetGroups()
    {
        $filter = '(&(objectClass=kolabGroupOfNames)(member='
            . Horde_LDAP::quote('cn=The Administrator,dc=example,dc=org') . '))';
        $result = $this->ldap->search($filter, array());
        $this->assertNoError($result);
        $this->assertTrue(!empty($result));

/*         $entry = $this->ldap->_firstEntry($result); */
/*         $this->assertNoError($entry); */
/*         $this->assertTrue(!empty($entry)); */

/*         $uid = $this->ldap->_getDn($entry); */
/*         $this->assertNoError($uid); */
/*         $this->assertTrue(!empty($uid)); */

/*         $entry = $this->ldap->_nextEntry($entry); */
/*         $this->assertNoError($entry); */
/*         $this->assertTrue(empty($entry)); */

/*         $entries = $this->ldap->_getDns($result); */
/*         $this->assertNoError($entries); */
/*         $this->assertTrue(!empty($entries)); */

        $groups = $this->ldap->getGroups('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($groups);
        $this->assertTrue(!empty($groups));

        $groups = $this->ldap->getGroups($this->ldap->uidForIdOrMailOrAlias('g.wrobel@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('cn=group@example.org,dc=example,dc=org', $groups);

        $groups = $this->ldap->getGroups($this->ldap->uidForIdOrMailOrAlias('test@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('cn=group@example.org,dc=example,dc=org', $groups);

        $groups = $this->ldap->getGroups('nobody');
        $this->assertNoError($groups);
        $this->assertTrue(empty($groups));

    }

    /**
     * Test parsing of LDAP filters.
     *
     * @return NULL
     */
    public function testFilterParse()
    {
        $db = &Horde_Kolab_Server::factory('test', array());

        $a = $db->parse('(a=b)');
        $this->assertNoError($a);
        $this->assertEquals(array('att' => 'a', 'log' => '=', 'val' => 'b'),
                            $a);

        $a = $db->parse('(&(a=b)(c=d))');
        $this->assertNoError($a);
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => 'b'),
                                      array('att' => 'c', 'log' => '=', 'val' => 'd'),
                                  )), $a);

        $a = $db->parse('(&(a=1)(|(b=2)(c=3)))');
        $this->assertNoError($a);
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => '1'),
                                      array('op' => '|', 'sub' =>
                                            array(
                                                array('att' => 'b', 'log' => '=', 'val' => '2'),
                                                array('att' => 'c', 'log' => '=', 'val' => '3'),
                                            )))), $a);

        $a = $db->parseSub('(!(x=2))(b=1)');
        $this->assertNoError($a);
        $this->assertEquals(array(array('op' => '!', 'sub' =>
                                        array(
                                            array('att' => 'x', 'log' => '=', 'val' => '2'),
                                        )
                                  ),
                                  array('att' => 'b', 'log' => '=', 'val' => '1'),
                            ), $a);

        $a = $db->parse('(&(!(x=2))(b=1))');
        $this->assertNoError($a);
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('op' => '!', 'sub' =>
                                            array(
                                                array('att' => 'x', 'log' => '=', 'val' => '2'),
                                            )
                                      ),
                                      array('att' => 'b', 'log' => '=', 'val' => '1'),
                                  )), $a);

    }

    /**
     * Test searching in the simulated LDAP data.
     *
     * @return NULL
     */
    public function testSearch()
    {
/*         $db = &Horde_Kolab_Server::factory('test', */
/*                                            array('data' => */
/*                                                  array( */
/*                                                      'cn=a' => array( */
/*                                                          'dn' => 'cn=a', */
/*                                                          'data' => array( */
/*                                                              'a' => '1', */
/*                                                              'b' => '1', */
/*                                                              'c' => '1', */
/*                                                          ) */
/*                                                      ), */
/*                                                      'cn=b' => array( */
/*                                                          'dn' => 'cn=b', */
/*                                                          'data' => array( */
/*                                                              'a' => '1', */
/*                                                              'b' => '2', */
/*                                                              'c' => '2', */
/*                                                          ) */
/*                                                      ), */
/*                                                      'cn=c' => array( */
/*                                                          'dn' => 'cn=c', */
/*                                                          'data' => array( */
/*                                                              'a' => '1', */
/*                                                              'b' => '2', */
/*                                                              'c' => '3', */
/*                                                          ) */
/*                                                      ), */
/*                                                      'cn=d' => array( */
/*                                                          'dn' => 'cn=d', */
/*                                                          'data' => array( */
/*                                                              'a' => '2', */
/*                                                              'b' => '2', */
/*                                                              'c' => '1', */
/*                                                          ) */
/*                                                      ), */
/*                                                  ) */
/*                                            ) */
/*         ); */

/*         $a = $db->search('(c=1)'); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=a', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                         'b' => '1', */
/*                         'c' => '1', */
/*                     ) */
/*                 ), */
/*                 array( */
/*                     'dn' => 'cn=d', */
/*                     'data' => array( */
/*                         'a' => '2', */
/*                         'b' => '2', */
/*                         'c' => '1', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(c=3)'); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=c', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                         'b' => '2', */
/*                         'c' => '3', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(c=3)', array('a')); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=c', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(&(a=1)(b=2))', array('a', 'b')); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=b', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                         'b' => '2', */
/*                     ) */
/*                 ), */
/*                 array( */
/*                     'dn' => 'cn=c', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                         'b' => '2', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(&(b=2))', array('b')); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=b', */
/*                     'data' => array( */
/*                         'b' => '2', */
/*                     ) */
/*                 ), */
/*                 array( */
/*                     'dn' => 'cn=c', */
/*                     'data' => array( */
/*                         'b' => '2', */
/*                     ) */
/*                 ), */
/*                 array( */
/*                     'dn' => 'cn=d', */
/*                     'data' => array( */
/*                         'b' => '2', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(!(b=2))', array('a', 'b')); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=a', */
/*                     'data' => array( */
/*                         'a' => '1', */
/*                         'b' => '1', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */

/*         $a = $db->_search('(&(!(x=2))(b=1))', array('b')); */
/*         $this->assertNoError($a); */
/*         $this->assertEquals( */
/*             array( */
/*                 array( */
/*                     'dn' => 'cn=a', */
/*                     'data' => array( */
/*                         'b' => '1', */
/*                     ) */
/*                 ), */
/*             ), */
/*             $a */
/*         ); */
    }

}
