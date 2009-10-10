<?php
/**
 * Test the handler for a simple LDAP setup without read-only slaves.
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
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/../Autoload.php';

/**
 * Test the handler for a simple LDAP setup without read-only slaves.
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
class Horde_Kolab_Server_Connection_SimpleldapTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!extension_loaded('ldap') && !@dl('ldap.' . PHP_SHLIB_SUFFIX)) {
            $this->markTestSuiteSkipped('Ldap extension is missing!');
        };

        if (!class_exists('Net_LDAP2')) {
            $this->markTestSuiteSkipped('PEAR package Net_LDAP2 is not installed!');
        }
    }

    public function testMethodConstructHasParameterNetldap2Connection()
    {
        $ldap = $this->getMock('Net_LDAP2');
        $conn = new Horde_Kolab_Server_Connection_Simpleldap($ldap);
    }

    public function testMethodConstructHasPostconditionThatTheGivenServerWasStored()
    {
        $ldap = $this->getMock('Net_LDAP2');
        $conn = new Horde_Kolab_Server_Connection_Simpleldap($ldap);
        $this->assertSame($ldap, $conn->getRead());
    }

    public function testMethodGetreadHasResultNetldap2TheHandledConnection()
    {
        $ldap = $this->getMock('Net_LDAP2');
        $conn = new Horde_Kolab_Server_Connection_Simpleldap($ldap);
        $this->assertType('Net_LDAP2', $conn->getRead());
    }

    public function testMethodGetwriteHasResultNetldap2TheHandledConnection()
    {
        $ldap = $this->getMock('Net_LDAP2');
        $conn = new Horde_Kolab_Server_Connection_Simpleldap($ldap);
        $this->assertSame($conn->getWrite(), $conn->getRead());
    }
}
