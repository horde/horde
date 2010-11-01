<?php
/**
 * Skip LDAP based tests if we don't have ldap or Horde_Ldap.
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
require_once dirname(__FILE__) . '/TestCase.php';

/**
 * Skip LDAP based tests if we don't have ldap or Horde_Ldap.
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