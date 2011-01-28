<?php
/**
 * Test the handler for a LDAP master/slave setup.
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
 * Test the handler for a LDAP master/slave setup.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Class_Server_Connection_SplittedldapTest
extends Horde_Kolab_Server_LdapTestCase
{
    public function testMethodConstructHasParameterNetldap2ReadConnectionAndParameterNetldap2WriteConnection()
    {
        $ldap_read = $this->getMock('Horde_Ldap');
        $ldap_write = $this->getMock('Horde_Ldap');
        $conn = new Horde_Kolab_Server_Connection_Splittedldap($ldap_read, $ldap_write);
    }

    public function testMethodConstructHasPostconditionThatTheGivenServersWereStored()
    {
        $ldap_read = $this->getMock('Horde_Ldap');
        $ldap_write = $this->getMock('Horde_Ldap');
        $conn = new Horde_Kolab_Server_Connection_Splittedldap($ldap_read, $ldap_write);
        $this->assertSame($ldap_read, $conn->getRead());
        $this->assertSame($ldap_write, $conn->getWrite());
    }

    public function testMethodGetreadHasResultNetldap2TheHandledConnection()
    {
        $ldap_read = $this->getMock('Horde_Ldap');
        $ldap_write = $this->getMock('Horde_Ldap');
        $conn = new Horde_Kolab_Server_Connection_Splittedldap($ldap_read, $ldap_write);
        $this->assertType('Horde_Ldap', $conn->getRead());
        $this->assertType('Horde_Ldap', $conn->getWrite());
    }

    public function testMethodGetwriteHasResultNetldap2TheHandledConnection()
    {
        $ldap_read = $this->getMock('Horde_Ldap');
        $ldap_write = $this->getMock('Horde_Ldap');
        $conn = new Horde_Kolab_Server_Connection_Splittedldap($ldap_read, $ldap_write);
        $this->assertFalse($conn->getWrite() === $conn->getRead());
    }
}
