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
 * Require our basic test case definition
 */
require_once dirname(__FILE__) . '/../../../../LdapTestCase.php';

/**
 * Test the test backend.
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
class Horde_Kolab_Server_Class_Server_Connection_Mock_LdapTest
extends Horde_Kolab_Server_LdapTestCase
{
    /**
     * Test parsing of LDAP filters.
     *
     * @return NULL
     */
    public function testFilterParse()
    {
        $this->skipIfNoLdap();

        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(array(), array());

        $a = $conn->parse('(a=b)');
        $this->assertEquals(array('att' => 'a', 'log' => '=', 'val' => 'b'),
                            $a);

        $a = $conn->parse('(&(a=b)(c=d))');
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => 'b'),
                                      array('att' => 'c', 'log' => '=', 'val' => 'd'),
                                  )), $a);

        $a = $conn->parse('(&(a=1)(|(b=2)(c=3)))');
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('att' => 'a', 'log' => '=', 'val' => '1'),
                                      array('op' => '|', 'sub' =>
                                            array(
                                                array('att' => 'b', 'log' => '=', 'val' => '2'),
                                                array('att' => 'c', 'log' => '=', 'val' => '3'),
                                            )))), $a);

        $a = $conn->parseSub('(!(x=2))(b=1)');
        $this->assertEquals(array(array('op' => '!', 'sub' =>
                                        array(
                                            array('att' => 'x', 'log' => '=', 'val' => '2'),
                                        )
                                  ),
                                  array('att' => 'b', 'log' => '=', 'val' => '1'),
                            ), $a);

        $a = $conn->parse('(&(!(x=2))(b=1))');
        $this->assertEquals(array('op' => '&', 'sub' => array(
                                      array('op' => '!', 'sub' =>
                                            array(
                                                array('att' => 'x', 'log' => '=', 'val' => '2'),
                                            )
                                      ),
                                      array('att' => 'b', 'log' => '=', 'val' => '1'),
                                  )), $a);

        try {
            $a = $conn->parse('dummy');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Filter parsing error: dummy - filter components must be enclosed in round brackets', $e->getMessage());
        }

        try {
            $a = $conn->parse('(a/b)');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Filter parsing error: invalid filter syntax - unknown matching rule used', $e->getMessage());
        }

        try {
            $a = $conn->parse('(a=b)(c=d)');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Filter parsing error: invalid filter syntax - multiple leaf components detected!', $e->getMessage());
        }
    }

    /**
     * Test searching in the simulated LDAP data.
     *
     * @return NULL
     */
    public function testSearch()
    {
        $this->skipIfNoLdap();

        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
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
        );
        $conn->bind();

        $a = $conn->search(null, '(c=1)');
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
            $a->asArray()
        );

        $a = $conn->search(null, '(c=3)');
        $this->assertEquals(
            array(
                'cn=c' => array(
                    'a' => '1',
                    'b' => '2',
                    'c' => '3',
                    'dn' => 'cn=c',
                ),
            ),
            $a->asArray()
        );

        $a = $conn->search(null, '(c=3)', array('attributes' => array('a')));
        $this->assertEquals(
            array(
                'cn=c' => array(
                    'a' => '1',
                    'dn' => 'cn=c',
                ),
            ),
            $a->asArray()
        );

        $a = $conn->search(null, '(&(a=1)(b=2))', array('attributes' => array('a', 'b')));
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
            $a->asArray()
        );

        $a = $conn->search(null, '(|(a=1)(b=2))', array('attributes' => array('a', 'b')));
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'a' => '1',
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
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
                'cn=d' => array(
                    'a' => '2',
                    'b' => '2',
                    'dn' => 'cn=d',
                )
            ),
            $a->asArray()
        );

        $a = $conn->search(null, '(&(b=2))', array('attributes' => array('b')));
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
            $a->asArray()
        );

        $a = $conn->search(null, '(!(b=2))', array('attributes' => array('a', 'b')));
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'a' => '1',
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
            ),
            $a->asArray()
        );

        $a = $conn->search(null, '(&(!(x=2))(b=1))', array('attributes' => array('b')));
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
            ),
            $a->asArray()
        );

        $a = $conn->search(null, '(c=t)', array('attributes' => array('a')));
        $this->assertEquals(
            array(),
            $a->asArray()
        );

        try {
            $a = $conn->search(null, '(c>t)', array('attributes' => array('a')));
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Not implemented!', $e->getMessage());
        }
    }

    public function testMethodBindHasPostconditionThatBindingOccursWithDefaultDnAndPwIfSpecified()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array('binddn' => 'test', 'bindpw' => 'test'),
            array()
        );
        try {
            $conn->bind('', '');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('User does not exist!', $e->getMessage());
        }
    }

    public function testMethodBindThrowsExceptionIfTheUserDoesNotExist()
    {
        $this->testMethodBindHasPostconditionThatBindingOccursWithDefaultDnAndPwIfSpecified();
    }

    public function testMethodBindThrowsExceptionIfTheUserHasNoPassword()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array('binddn' => 'test', 'bindpw' => 'test'),
            array('test' =>
                  array('data' =>
                        array()
                  )
            )
        );
        try {
            $conn->bind('', '');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('User has no password entry!', $e->getMessage());
        }
    }

    public function testMethodBindThrowsExceptionIfThePasswordWasIncorrect()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array('binddn' => 'test', 'bindpw' => 'test'),
            array('test' =>
                  array('data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  )
            )
        );
        try {
            $conn->bind('', '');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Incorrect password!', $e->getMessage());
        }
    }

    public function testMethodBindThrowsExceptionIfAnonymousLoginIsDisabledAndTheDnIsUnset()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array('no_anonymous_bind' => true),
            array()
        );
        try {
            $conn->bind('', '');
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Anonymous bind is not allowed!', $e->getMessage());
        }
    }

    public function testMethodSearchHasResultMocksearchSingleElementIfNoFilterIsSetAndSearchScopeIsBase()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array('test' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  ),
                  'testnot' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  )
            )
        );
        $conn->bind('', '');
        $result = $conn->search('test', null, array('scope' => 'base'));
        $this->assertEquals(
            array('test' => array(
                      'userPassword' => array('something'),
                      'dn' => 'test'
                  )
            ),
            $result->asArray()
        );
    }

    public function testMethodSearchHasResultMocksearchEmptyIfNoFilterIsSetSearchScopeIsBaseAndTheSpecifiedBaseDoesNotExist()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array()
        );
        $conn->bind('', '');
        $result = $conn->search('test', null, array('scope' => 'base'));
        $this->assertEquals(
            array(),
            $result->asArray()
        );
    }

    public function testMethodSearchHasResultMocksearchSingleElementIfNoFilterIsSetSearchScopeIsSubAndOnlyOneElementMatchesBase()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array('test' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  ),
                  'testnot' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  )
            )
        );
        $conn->bind('', '');
        $result = $conn->search('test', null, array('scope' => 'sub'));
        $this->assertEquals(
            array('test' => array(
                      'userPassword' => array('something'),
                      'dn' => 'test'
                  )
            ),
            $result->asArray()
        );
    }

    public function testMethodSearchHasResultMocksearchWithMatchingElementsIfNoSearchScopeIsSet()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array('test' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  ),
                  'yestest' =>
                  array('dn' => 'yestest',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  ),
                  'testnot' =>
                  array('dn' => 'testnot',
                        'data' =>
                        array(
                            'userPassword' => array('something')
                        )
                  )
            )
        );
        $conn->bind('', '');
        $result = $conn->search('test');
        $this->assertEquals(
            array('test', 'yestest'),
            array_keys($result->asArray())
        );
    }

    public function testMethodSearchHasResultMocksearchWithSelectedAttributesIfSpecificAttributesWereSet()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array('test' =>
                  array('dn' => 'test',
                        'data' =>
                        array(
                            'userPassword' => array('something'),
                            'name' => array('name1')
                        )
                  ),
                  'yestest' =>
                  array('dn' => 'yestest',
                        'data' =>
                        array(
                            'userPassword' => array('something'),
                            'name' => array('name2')
                        )
                  ),
                  'testnot' =>
                  array('dn' => 'testnot',
                        'data' =>
                        array(
                            'userPassword' => array('something'),
                            'name' => array('name2')
                        )
                  )
            )
        );
        $conn->bind('', '');
        $result = $conn->search('test', null, array('attributes' => 'name'));
        $this->assertEquals(
            array(
                'test' => array(
                    'name' => array('name1'),
                    'dn' => 'test'
                ),
                'yestest' => array(
                    'name' => array('name2'),
                    'dn' => 'yestest'
                ),
            ),
            $result->asArray()
        );
    }

    public function testMethodSearchThrowsExceptionIfSearchScopeIsOne()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array()
        );
        $conn->bind('', '');
        try {
            $result = $conn->search('test', null, array('scope' => 'one'));
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Not implemented!', $e->getMessage());
        }
    }

    public function testMethodSearchThrowsExceptionIfTheConnectionIsNotBound()
    {
        $conn = new Horde_Kolab_Server_Connection_Mock_Ldap(
            array(),
            array()
        );
        try {
            $conn->search();
            $this->fail('No exception!');
        } catch (Horde_Kolab_Server_Exception $e) {
            $this->assertEquals('Unbound connection!', $e->getMessage());
        }
    }

}
