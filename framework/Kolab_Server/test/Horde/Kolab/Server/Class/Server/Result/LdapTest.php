<?php
/**
 * Test the LDAP result handler.
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
 * Test the LDAP result handler.
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
class Horde_Kolab_Server_Class_Server_Result_LdapTest extends Horde_Kolab_Server_LdapTestCase
{
    public function testMethodConstructHasParameterNetldap2searchSearchResult()
    {
        $search = $this->getMock(
            'Horde_Ldap_Search', array(), array(), '', false
        );
        $result = new Horde_Kolab_Server_Result_Ldap($search);
    }


    public function testMethodCountHasResultIntTheNumberOfElementsFound()
    {
        $search = $this->getMock(
            'Horde_Ldap_Search', array('count'), array(), '', false
        );
        $search->expects($this->exactly(1))
            ->method('count')
            ->will($this->returnValue(1));
        $result = new Horde_Kolab_Server_Result_Ldap($search);
        $this->assertEquals(1, $result->count());
    }

    public function testMethodSizelimitexceededHasResultBooleanIndicatingIfTheSearchSizeLimitWasHit()
    {
        $search = $this->getMock(
            'Horde_Ldap_Search', array('sizeLimitExceeded'), array(), '', false
        );
        $search->expects($this->exactly(1))
            ->method('sizeLimitExceeded')
            ->will($this->returnValue(true));
        $result = new Horde_Kolab_Server_Result_Ldap($search);
        $this->assertTrue($result->sizeLimitExceeded());
    }

    public function testMethodAsarrayHasResultArrayWithTheSearchResults()
    {
        $search = $this->getMock(
            'Horde_Ldap_Search', array('asArray'), array(), '', false
        );
        $search->expects($this->exactly(1))
            ->method('asArray')
            ->will($this->returnValue(array('a' => 'a')));
        $result = new Horde_Kolab_Server_Result_Ldap($search);
        $this->assertEquals(array('a' => 'a'), $result->asArray());
    }
}
