<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

class Horde_Ldap_EntryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testCreateFreshFail()
    {
        $entry = Horde_Ldap_Entry::createFresh('cn=test', 'I should be an array');
    }

    public function testCreateFreshSuccess()
    {
        $entry = Horde_Ldap_Entry::createFresh('cn=test',
                                               array('attr1' => 'single',
                                                     'attr2' => array('mv1', 'mv2')));
        $this->assertType('Horde_Ldap_Entry', $entry);
    }
}
