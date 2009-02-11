<?php
/**
 * Test the test driver.
 *
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
 * Test the test backend.
 *
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
        $result = $this->ldap->_search('(objectClass=top)', array('objectClass'));
        $this->assertNoError($result);
        $this->assertEquals(12, count($result));
      
        $result = $this->ldap->_search('(objectClass=top)', array('objectClass'),
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
        $result = $this->ldap->_search('(mail=*)', array('mail'));
        $this->assertNoError($result);
        $this->assertEquals(5, count($result));
        $this->ldap->_sort($result, 'mail');
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
        $result = $this->ldap->_search($filter);
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));

        $result = $this->ldap->listObjects(KOLAB_OBJECT_USER);
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals(KOLAB_OBJECT_USER, get_class($result[0]));

        $result = $this->ldap->listObjects(KOLAB_OBJECT_SHAREDFOLDER);
        $this->assertNoError($result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(KOLAB_OBJECT_SHAREDFOLDER, get_class($result[0]));
    }

    /**
     * Test handling of object classes.
     *
     * @return NULL
     */
    public function testGetObjectClasses()
    {
        $classes = $this->ldap->_getObjectClasses('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($classes);
        $this->assertContains('top', $classes);
        $this->assertContains('kolabinetorgperson', $classes);
        $this->assertContains('hordeperson', $classes);

        $classes = $this->ldap->_getObjectClasses('cn=DOES NOT EXIST,dc=example,dc=org');
        $this->assertError($classes,
                           'LDAP Error: No such object: cn=DOES NOT EXIST,dc=example,dc=org: No such object');

        $classes = $this->ldap->_getObjectClasses('cn=The Administrator,dc=example,dc=org');
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
        $this->assertEquals(KOLAB_OBJECT_GROUP, $type);

        $type = $this->ldap->determineType('cn=shared@example.org,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_SHAREDFOLDER, $type);

        $type = $this->ldap->determineType('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_ADMINISTRATOR, $type);

        $type = $this->ldap->determineType('cn=Main Tainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_MAINTAINER, $type);

        $type = $this->ldap->determineType('cn=Domain Maintainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_DOMAINMAINTAINER, $type);

        $type = $this->ldap->determineType('cn=Test Address,cn=external,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_ADDRESS, $type);

        $type = $this->ldap->determineType('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals(KOLAB_OBJECT_USER, $type);
    }

    /**
     * Test retrieving a primary mail for a mail or id.
     *
     * @return NULL
     */
    public function testMailForIdOrMail()
    {
        $mail = $this->ldap->mailForIdOrMail('wrobel');
        $this->assertNoError($mail);
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
        $uid = $this->ldap->uidForMailOrIdOrAlias('g.wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailOrIdOrAlias('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailOrIdOrAlias('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailOrIdOrAlias('DOES NOT EXIST');
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
        $uid = $this->ldap->uidForMailAddress('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailAddress('test@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Test Test,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailAddress('gunnar@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $this->ldap->uidForMailAddress('wrobel');
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
        $uid = $this->ldap->uidForAttr('alias', 'g.wrobel@example.org');
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
        $uid = $this->ldap->uidForMailAddress('g.wrobel@example.org');
        $this->assertNoError($uid);
        $member = $this->ldap->memberOfGroupAddress($uid, 'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $this->ldap->memberOfGroupAddress(
            $this->ldap->uidForMailAddress('test@example.org'),
            'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $this->ldap->memberOfGroupAddress(
            $this->ldap->uidForMailAddress('somebody@example.org'),
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
        $result = $this->ldap->_search($filter, array());
        $this->assertNoError($result);
        $this->assertTrue(!empty($result));

        $entry = $this->ldap->_firstEntry($result);
        $this->assertNoError($entry);
        $this->assertTrue(!empty($entry));

        $uid = $this->ldap->_getDn($entry);
        $this->assertNoError($uid);
        $this->assertTrue(!empty($uid));

        $entry = $this->ldap->_nextEntry($entry);
        $this->assertNoError($entry);
        $this->assertTrue(empty($entry));

        $entries = $this->ldap->_getDns($result);
        $this->assertNoError($entries);
        $this->assertTrue(!empty($entries));

        $groups = $this->ldap->getGroups('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($groups);
        $this->assertTrue(!empty($groups));

        $groups = $this->ldap->getGroups($this->ldap->uidForMailAddress('g.wrobel@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('cn=group@example.org,dc=example,dc=org', $groups);

        $groups = $this->ldap->getGroups($this->ldap->uidForMailAddress('test@example.org'));
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

        $a = $db->_parse('(a=b)');
        $this->assertNoError($a);
        $this->assertEquals(array('att' => 'a', 'log' => '=', 'val' => 'b'),
                            $a);

        $a = $db->_parse('(&(a=b)(c=d))');
        $this->assertNoError($a);
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => 'b'),
                                      array('att' => 'c', 'log' => '=', 'val' => 'd'),
                                  )), $a);

        $a = $db->_parse('(&(a=1)(|(b=2)(c=3)))');
        $this->assertNoError($a);
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => '1'),
                                      array('op' => '|', 'sub' =>
                                            array(
                                                array('att' => 'b', 'log' => '=', 'val' => '2'),
                                                array('att' => 'c', 'log' => '=', 'val' => '3'),
                                            )))), $a);

        $a = $db->_parseSub('(!(x=2))(b=1)');
        $this->assertNoError($a);
        $this->assertEquals(array(array('op' => '!', 'sub' =>
                                        array(
                                            array('att' => 'x', 'log' => '=', 'val' => '2'),
                                        )
                                  ),
                                  array('att' => 'b', 'log' => '=', 'val' => '1'),
                            ), $a);

        $a = $db->_parse('(&(!(x=2))(b=1))');
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
        $db = &Horde_Kolab_Server::factory('test',
                                           array('data' =>
                                                 array(
                                                     'cn=a' => array(
                                                         'dn' => 'cn=a',
                                                         'data' => array(
                                                             'a' => '1',
                                                             'b' => '1',
                                                             'c' => '1',
                                                         )
                                                     ),
                                                     'cn=b' => array(
                                                         'dn' => 'cn=b',
                                                         'data' => array(
                                                             'a' => '1',
                                                             'b' => '2',
                                                             'c' => '2',
                                                         )
                                                     ),
                                                     'cn=c' => array(
                                                         'dn' => 'cn=c',
                                                         'data' => array(
                                                             'a' => '1',
                                                             'b' => '2',
                                                             'c' => '3',
                                                         )
                                                     ),
                                                     'cn=d' => array(
                                                         'dn' => 'cn=d',
                                                         'data' => array(
                                                             'a' => '2',
                                                             'b' => '2',
                                                             'c' => '1',
                                                         )
                                                     ),
                                                 )
                                           )
        );

        $a = $db->_search('(c=1)');
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=a',
                    'data' => array(
                        'a' => '1',
                        'b' => '1',
                        'c' => '1',
                    )
                ),
                array(
                    'dn' => 'cn=d',
                    'data' => array(
                        'a' => '2',
                        'b' => '2',
                        'c' => '1',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(c=3)');
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=c',
                    'data' => array(
                        'a' => '1',
                        'b' => '2',
                        'c' => '3',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(c=3)', array('a'));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=c',
                    'data' => array(
                        'a' => '1',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(&(a=1)(b=2))', array('a', 'b'));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=b',
                    'data' => array(
                        'a' => '1',
                        'b' => '2',
                    )
                ),
                array(
                    'dn' => 'cn=c',
                    'data' => array(
                        'a' => '1',
                        'b' => '2',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(&(b=2))', array('b'));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=b',
                    'data' => array(
                        'b' => '2',
                    )
                ),
                array(
                    'dn' => 'cn=c',
                    'data' => array(
                        'b' => '2',
                    )
                ),
                array(
                    'dn' => 'cn=d',
                    'data' => array(
                        'b' => '2',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(!(b=2))', array('a', 'b'));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=a',
                    'data' => array(
                        'a' => '1',
                        'b' => '1',
                    )
                ),
            ),
            $a
        );

        $a = $db->_search('(&(!(x=2))(b=1))', array('b'));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                array(
                    'dn' => 'cn=a',
                    'data' => array(
                        'b' => '1',
                    )
                ),
            ),
            $a
        );
    }

}
