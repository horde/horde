<?php
/**
 * Skip LDAP based tests if we don't have ldap or Horde_Ldap.
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
require_once __DIR__ . '/TestCase.php';

/**
 * Skip LDAP based tests if we don't have ldap or Horde_Ldap.
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
class Horde_Kolab_Server_LdapTestCase extends Horde_Kolab_Server_TestCase
{
    public function skipIfNoLdap()
    {
        if (!extension_loaded('ldap') && !@dl('ldap.' . PHP_SHLIB_SUFFIX)) {
            $this->markTestSkipped('Ldap extension is missing!');
        };

        if (!class_exists('Horde_Ldap')) {
            $this->markTestSkipped('Horde_Ldap is not installed!');
        }
    }
}