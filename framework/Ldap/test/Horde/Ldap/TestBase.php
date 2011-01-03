<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

class Horde_Ldap_TestBase extends Horde_Test_Case
{
    protected static $ldapcfg;

    public function setUp()
    {
        // Check extension.
        try {
            Horde_Ldap::checkLDAPExtension();
        } catch (Horde_Ldap_Exception $e) {
            $this->markTestSkipped($e->getMessage());
        }

        $config = $this->getConfig('LDAP_TEST_CONFIG');
        if (!$config) {
            $this->markTestSkipped('No configuration for LDAP tests.');
        }
        self::$ldapcfg = $config;
    }
}
