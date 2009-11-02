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
            $a->as_struct()
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
            $a->as_struct()
        );

        $a = $conn->search(null, '(c=3)', array('attributes' => array('a')));
        $this->assertEquals(
            array(
                'cn=c' => array(
                    'a' => '1',
                    'dn' => 'cn=c',
                ),
            ),
            $a->as_struct()
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
            $a->as_struct()
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
            $a->as_struct()
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
            $a->as_struct()
        );

        $a = $conn->search(null, '(&(!(x=2))(b=1))', array('attributes' => array('b')));
        $this->assertEquals(
            array(
                'cn=a' => array(
                    'b' => '1',
                    'dn' => 'cn=a',
                ),
            ),
            $a->as_struct()
        );
    }

}
