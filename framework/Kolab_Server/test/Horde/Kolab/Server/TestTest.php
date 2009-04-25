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
     * Test search base.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testSearchBase($type)
    {
        $server = &$this->prepareBasicServer($type);

        $result = $server->search(
            '(' . Horde_Kolab_Server_Object::ATTRIBUTE_OC
            . '=' . Horde_Kolab_Server_Object::OBJECTCLASS_TOP . ')',
            array(Horde_Kolab_Server_Object::ATTRIBUTE_OC));
        $this->assertEquals(13, count($result));
      
        $result = $server->search(
            '(' . Horde_Kolab_Server_Object::ATTRIBUTE_OC
            . '=' . Horde_Kolab_Server_Object::OBJECTCLASS_TOP . ')',
            array(Horde_Kolab_Server_Object::ATTRIBUTE_OC),
            'cn=internal,dc=example,dc=org');
        $this->assertNoError($result);
        $this->assertEquals(4, count($result));
    }

    /**
     * Test sorting.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testSorting($type)
    {
        $server = &$this->prepareBasicServer($type);

/*         $result = $server->search('(mail=*)', array('mail')); */
/*         $this->assertNoError($result); */
/*         $this->assertEquals(5, count($result)); */
/*         $server->sort($result, 'mail'); */
/*         foreach ($result as $object) { */
/*             if (isset($object['data']['dn'])) { */
/*                 switch ($object['data']['dn']) { */
/*                 case 'cn=Test Address,cn=external,dc=example,dc=org': */
/*                     $this->assertContains('address@example.org', $object['data']['mail']); */
/*                     break; */
/*                 case '': */
/*                     $this->assertContains('address@example.org', $object['data']['mail']); */
/*                     break; */
/*                 } */
/*             } */
/*         } */
    }

    /**
     * Test listing objects.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testListObjects($type)
    {
        $server = &$this->prepareBasicServer($type);

        $filter     = '(&(objectClass=kolabInetOrgPerson)(uid=*)(mail=*)(sn=*))';
        $attributes = array(
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SN,
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_CN,
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_UID,
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_MAIL,
            Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_DELETED,
        );

        $sort   = Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_SN;
        $result = $server->search($filter);
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));

        $result = $server->listObjects('Horde_Kolab_Server_Object_Kolab_User');
        $this->assertNoError($result);
        $this->assertEquals(2, count($result));
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_User', get_class(array_shift($result)));

        $result = $server->listObjects('Horde_Kolab_Server_Object_Kolabsharedfolder');
        $this->assertNoError($result);
        $this->assertEquals(1, count($result));
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabsharedfolder', get_class(array_shift($result)));
    }

    /**
     * Test handling of object classes.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testGetObjectClasses($type)
    {
        $server = &$this->prepareBasicServer($type);

        $classes = $server->getObjectClasses('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($classes);
        $this->assertContains('top', $classes);
        $this->assertContains('kolabinetorgperson', $classes);

        try {
            $classes = $server->getObjectClasses('cn=DOES NOT EXIST,dc=example,dc=org');
        } catch (Horde_Kolab_Server_Exception $classes) {
        }
        $this->assertError($classes,
                           'No such object: cn=DOES NOT EXIST,dc=example,dc=org');

        $classes = $server->getObjectClasses('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($classes);
        $this->assertContains('kolabinetorgperson', $classes);
    }

    /**
     * Test handling of object types.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testDetermineType($type)
    {
        $server = &$this->prepareBasicServer($type);

        $type = $server->determineType('cn=empty.group@example.org,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabgroupofnames', $type);

        $type = $server->determineType('cn=shared@example.org,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolabsharedfolder', $type);

        $type = $server->determineType('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_Administrator', $type);

        $type = $server->determineType('cn=Main Tainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_Maintainer', $type);

        $type = $server->determineType('cn=Domain Maintainer,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_Domainmaintainer', $type);

        $type = $server->determineType('cn=Test Address,cn=external,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_Address', $type);

        $type = $server->determineType('cn=Gunnar Wrobel,dc=example,dc=org');
        $this->assertNoError($type);
        $this->assertEquals('Horde_Kolab_Server_Object_Kolab_User', $type);
    }

    /**
     * Test retrieving a primary mail for a mail or id.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testMailForIdOrMail($type)
    {
        $server = &$this->prepareBasicServer($type);

        $mail = $server->mailForIdOrMail('wrobel');
        $this->assertEquals('wrobel@example.org', $mail);

        $mail = $server->mailForIdOrMail('wrobel@example.org');
        $this->assertNoError($mail);
        $this->assertEquals('wrobel@example.org', $mail);

        $mail = $server->mailForIdOrMail('DOES NOT EXIST');
        $this->assertNoError($mail);
        $this->assertSame(false, $mail);
    }

    /**
     * Test retrieving a UID for a mail or id.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testUidForIdOrMail($type)
    {
        $server = &$this->prepareBasicServer($type);

        $uid = $server->uidForIdOrMail('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMail('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMail('DOES NOT EXIST');
        $this->assertNoError($uid);
        $this->assertSame(false, $uid);
    }

    /**
     * Test retrieving a UID for a mail or id.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testUidForMailOrIdOrAlias($type)
    {
        $server = &$this->prepareBasicServer($type);

        $uid = $server->uidForIdOrMailOrAlias('g.wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('DOES NOT EXIST');
        $this->assertNoError($uid);
        $this->assertSame(false, $uid);
    }

    /**
     * Test retrieving all addresses for a mail or id.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testAddrsForIdOrMail($type)
    {
        $server = &$this->prepareBasicServer($type);

        $addrs = $server->addrsForIdOrMail('wrobel');

        $testuser = $server->fetch('cn=Test Test,dc=example,dc=org');
        $this->assertNoError($testuser);
        $this->assertContains('wrobel@example.org',
                              $testuser->get(Horde_Kolab_Server_Object_Kolabinetorgperson::ATTRIBUTE_DELEGATE, false));

        $this->assertNoError($addrs);
        $this->assertContains('wrobel@example.org', $addrs);
        $this->assertContains('test@example.org', $addrs);
        $this->assertContains('t.test@example.org', $addrs);
        $this->assertContains('g.wrobel@example.org', $addrs);
        $this->assertContains('gunnar@example.org', $addrs);

        $addrs = $server->addrsForIdOrMail('test@example.org');
        $this->assertNoError($addrs);
        $this->assertContains('test@example.org', $addrs);
        $this->assertContains('t.test@example.org', $addrs);
    }

    /**
     * Test retrieving a UID for a primary mail.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testUidForMailAddress($type)
    {
        $server = &$this->prepareBasicServer($type);

        $uid = $server->uidForIdOrMailOrAlias('wrobel@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('test@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Test Test,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('gunnar@example.org');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);

        $uid = $server->uidForIdOrMailOrAlias('wrobel');
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);
    }

    /**
     * Test retrieving a UID for an attribute.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testUidForAttr($type)
    {
        $server = &$this->prepareBasicServer($type);

        $uid = $server->uidForSearch(array('AND' => array(array('field' => 'alias',
                                                                    'op' => '=',
                                                                    'test' => 'g.wrobel@example.org'))));
        $this->assertNoError($uid);
        $this->assertEquals('cn=Gunnar Wrobel,dc=example,dc=org', $uid);
    }

    /**
     * Test group membership testing.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testMemberOfGroupAddress($type)
    {
        $server = &$this->prepareBasicServer($type);

        $uid = $server->uidForIdOrMailOrAlias('g.wrobel@example.org');
        $this->assertNoError($uid);
        $member = $server->memberOfGroupAddress($uid, 'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $server->memberOfGroupAddress(
            $server->uidForIdOrMailOrAlias('test@example.org'),
            'group@example.org');
        $this->assertNoError($member);
        $this->assertTrue($member);

        $member = $server->memberOfGroupAddress(
            $server->uidForIdOrMailOrAlias('somebody@example.org'),
            'group@example.org');
        $this->assertNoError($member);
        $this->assertFalse($member);
    }

    /**
     * Test group fetching.
     *
     * @dataProvider provideServerTypes
     *
     * @return NULL
     */
    public function testGetGroups($type)
    {
        $server = &$this->prepareBasicServer($type);

        $filter = '(&(objectClass=kolabGroupOfNames)(member='
            . Horde_LDAP::quote('cn=The Administrator,dc=example,dc=org') . '))';
        $result = $server->search($filter, array());
        $this->assertNoError($result);
        $this->assertTrue(!empty($result));

/*         $entry = $server->_firstEntry($result); */
/*         $this->assertNoError($entry); */
/*         $this->assertTrue(!empty($entry)); */

/*         $uid = $server->_getDn($entry); */
/*         $this->assertNoError($uid); */
/*         $this->assertTrue(!empty($uid)); */

/*         $entry = $server->_nextEntry($entry); */
/*         $this->assertNoError($entry); */
/*         $this->assertTrue(empty($entry)); */

/*         $entries = $server->_getDns($result); */
/*         $this->assertNoError($entries); */
/*         $this->assertTrue(!empty($entries)); */

        $groups = $server->getGroups('cn=The Administrator,dc=example,dc=org');
        $this->assertNoError($groups);
        $this->assertTrue(!empty($groups));

        $groups = $server->getGroups($server->uidForIdOrMailOrAlias('g.wrobel@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('cn=group@example.org,dc=example,dc=org', $groups);

        $groups = $server->getGroupAddresses($server->uidForIdOrMailOrAlias('g.wrobel@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('group@example.org', $groups);

        $groups = $server->getGroups($server->uidForIdOrMailOrAlias('test@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('cn=group@example.org,dc=example,dc=org', $groups);

        $groups = $server->getGroupAddresses($server->uidForIdOrMailOrAlias('test@example.org'));
        $this->assertNoError($groups);
        $this->assertContains('group@example.org', $groups);

        $groups = $server->getGroups('nobody');
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

        $a = $db->search('(c=1)');
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'a' => '1',
                    'b' => '1',
                    'c' => '1',
                    'dn' => 'cn=a',
                ),
                'cn=d' => array(
                    'a' => '2',
                    'b' => '2',
                    'c' => '1',
                    'dn' => 'cn=d',
                ),
            ),
            $a
        );

        $a = $db->search('(c=3)');
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=c' => array(
                    'a' => '1',
                    'b' => '2',
                    'c' => '3',
                    'dn' => 'cn=c',
                ),
            ),
            $a
        );

        $a = $db->search('(c=3)', array('attributes' => array('a')));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=c' => array(
                    'a' => '1',
                    'dn' => 'cn=c',
                ),
            ),
            $a
        );

        $a = $db->search('(&(a=1)(b=2))', array('attributes' => array('a', 'b')));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=b' => array(
                    'a' => '1',
                    'b' => '2',
                    'dn' => 'cn=b',
                ),
                'cn=c' => array(
                    'a' => '1',
                    'b' => '2',
                    'dn' => 'cn=c',
                ),
            ),
            $a
        );

        $a = $db->search('(&(b=2))', array('attributes' => array('b')));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=b' => array(
                    'b' => '2',
                    'dn' => 'cn=b',
                ),
                'cn=c' => array(
                    'b' => '2',
                    'dn' => 'cn=c',
                ),
                'cn=d' => array(
                    'b' => '2',
                    'dn' => 'cn=d',
                ),
            ),
            $a
        );

        $a = $db->search('(!(b=2))', array('attributes' => array('a', 'b')));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'a' => '1',
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
            ),
            $a
        );

        $a = $db->search('(&(!(x=2))(b=1))', array('attributes' => array('b')));
        $this->assertNoError($a);
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
            ),
            $a
        );
    }

}
