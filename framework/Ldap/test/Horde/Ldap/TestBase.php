<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

class Horde_Ldap_TestBase extends PHPUnit_Framework_TestCase
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

        $file = dirname(__FILE__) . '/conf.php';
        if (!file_exists($file) || !is_readable($file)) {
            $this->markTestSkipped('conf.php cannot be opened.');
        }
        include $file;
        self::$ldapcfg = $conf;
    }
}
