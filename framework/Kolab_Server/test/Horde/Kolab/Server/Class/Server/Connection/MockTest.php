<?php
/**
 * Test the handler for a mock connection.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../../../Autoload.php';

/**
 * Test the handler for a mock connection.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Class_Server_Connection_MockTest
extends PHPUnit_Framework_TestCase
{
    public function testMethodConstructHasParameterMockldapConnection()
    {
        $ldap = $this->getMock(
            'Horde_Kolab_Server_Connection_Mock_Ldap',
            array(), array(), '', false, false
        );
        $conn = new Horde_Kolab_Server_Connection_Mock($ldap);
    }

    public function testMethodConstructHasPostconditionThatTheGivenServerWasStored()
    {
        $ldap = $this->getMock(
            'Horde_Kolab_Server_Connection_Mock_Ldap',
            array(), array(), '', false, false
        );
        $conn = new Horde_Kolab_Server_Connection_Mock($ldap);
        $this->assertSame($ldap, $conn->getRead());
    }

    public function testMethodGetreadHasResultMockldapTheHandledConnection()
    {
        $ldap = $this->getMock(
            'Horde_Kolab_Server_Connection_Mock_Ldap',
            array(), array(), '', false, false
        );
        $conn = new Horde_Kolab_Server_Connection_Mock($ldap);
        $this->assertInstanceOf('Horde_Kolab_Server_Connection_Mock_Ldap', $conn->getRead());
    }

    public function testMethodGetwriteHasResultMockldapTheHandledConnection()
    {
        $ldap = $this->getMock(
            'Horde_Kolab_Server_Connection_Mock_Ldap',
            array(), array(), '', false, false
        );
        $conn = new Horde_Kolab_Server_Connection_Mock($ldap);
        $this->assertSame($conn->getWrite(), $conn->getRead());
    }
}
