<?php
/**
 * Test the standard LDAP driver.
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
require_once dirname(__FILE__) . '/../../../LdapTestCase.php';

/**
 * Test the standard LDAP driver.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Ldap_StandardTest extends Horde_Kolab_Server_LdapTestCase
{
    public function setUp()
    {
        $this->skipIfNoLdap();

        $this->ldap_read  = $this->getMock('Horde_Ldap');
        $this->ldap_write = $this->getMock('Horde_Ldap');
        $connection = new Horde_Kolab_Server_Connection_Splittedldap(
            $this->ldap_read,
            $this->ldap_write
        );

        $this->server = new Horde_Kolab_Server_Ldap_Standard(
            $connection,
            'base'
        );
    }

    private function getSearchResultMock()
    {
        $result = $this->getMock(
            'Horde_Ldap_Search', array('asArray', 'count'), array(), '', false
        );
        $result->expects($this->any())
            ->method('asArray')
            ->will($this->returnValue(array(array('dn' => 'test'))));
        $result->expects($this->any())
            ->method('count')
            ->will($this->returnValue(1));
        return $result;
    }

    public function testMethodFindbelowHasParameterQueryelementTheSearchCriteria()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->findBelow('(equals=equals)', '');
    }

    public function testMethodFindbelowHasParameterStringParent()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('parent', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->findBelow('(equals=equals)', 'parent', array());
    }

    public function testMethodFindbelowHasParameterArrayAdditionalParameters()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->server->findBelow('(equals=equals)', '', array());
    }

    public function testMethodFindbelowReturnsArraySearchResult()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->with('parent', '(equals=equals)', array())
            ->will($this->returnValue($this->getSearchResultMock()));
        $this->assertEquals(
            array(array('dn' => 'test')),
            $this->server->findBelow('(equals=equals)', 'parent')->asArray()
        );
    }

    public function testMethodFindbelowThrowsExceptionIfTheSearchFailed()
    {
        $this->ldap_read->expects($this->exactly(1))
            ->method('search')
            ->will($this->throwException(new Horde_Ldap_Exception('Search failed!')));
        try {
            $this->assertEquals(array('dn' => 'test'), $this->server->findBelow('(equals=equals)', ''));
            $this->fail('No exception!');
        } catch (Exception $e) {
            $this->assertEquals('Search failed!', $e->getMessage());
            $this->assertEquals(Horde_Kolab_Server_Exception::SYSTEM, $e->getCode());
        }
    }

}
