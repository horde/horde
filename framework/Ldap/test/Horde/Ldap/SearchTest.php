<?php

require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_SearchTest extends Horde_Ldap_TestBase
{
    public static function tearDownAfterClass()
    {
        if (!self::$ldapcfg) {
            return;
        }
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $ldap->delete('ou=Horde_Ldap_Test_search1,' . self::$ldapcfg['server']['basedn']);
        $ldap->delete('ou=Horde_Ldap_Test_search2,' . self::$ldapcfg['server']['basedn']);
    }

    /**
     * Tests SPL iterator.
     */
    public function testSPLIterator()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Some testdata, so we have some entries to search for.
        $base = self::$ldapcfg['server']['basedn'];
        $ou1 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_search1,' . $base,
            array(
                'objectClass' => array('top', 'organizationalUnit'),
                'ou' => 'Horde_Ldap_Test_search1'));
        $ou2 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_search2,' . $base,
            array(
                'objectClass' => array('top', 'organizationalUnit'),
                'ou' => 'Horde_Ldap_Test_search2'));

        $ldap->add($ou1);
        $this->assertTrue($ldap->exists($ou1->dn()));
        $ldap->add($ou2);
        $this->assertTrue($ldap->exists($ou2->dn()));

        /* Search and test each method. */
        $search = $ldap->search(null, '(ou=Horde_Ldap*)');
        $this->assertType('Horde_Ldap_Search', $search);
        $this->assertEquals(2, $search->count());

        // current() is supposed to return first valid element.
        $e1 = $search->current();
        $this->assertType('Horde_Ldap_Entry', $e1);
        $this->assertEquals($e1->dn(), $search->key());
        $this->assertTrue($search->valid());

        // Shift to next entry.
        $search->next();
        $e2 = $search->current();
        $this->assertType('Horde_Ldap_Entry', $e2);
        $this->assertEquals($e2->dn(), $search->key());
        $this->assertTrue($search->valid());

        // Shift to non existent third entry.
        $search->next();
        $this->assertFalse($search->current());
        $this->assertFalse($search->key());
        $this->assertFalse($search->valid());

        // Rewind and test, which should return the first entry a second time.
        $search->rewind();
        $e1_1 = $search->current();
        $this->assertType('Horde_Ldap_Entry', $e1_1);
        $this->assertEquals($e1_1->dn(), $search->key());
        $this->assertTrue($search->valid());
        $this->assertEquals($e1->dn(), $e1_1->dn());

        // Don't rewind but call current, should return first entry again.
        $e1_2 = $search->current();
        $this->assertType('Horde_Ldap_Entry', $e1_2);
        $this->assertEquals($e1_2->dn(), $search->key());
        $this->assertTrue($search->valid());
        $this->assertEquals($e1->dn(), $e1_2->dn());

        // Rewind again and test, which should return the first entry a third
        // time.
        $search->rewind();
        $e1_3 = $search->current();
        $this->assertType('Horde_Ldap_Entry', $e1_3);
        $this->assertEquals($e1_3->dn(), $search->key());
        $this->assertTrue($search->valid());
        $this->assertEquals($e1->dn(), $e1_3->dn());

        /* Try methods on empty search result. */
        $search = $ldap->search(null, '(ou=Horde_LdapTest_NotExistentEntry)');
        $this->assertType('Horde_Ldap_Search', $search);
        $this->assertEquals(0, $search->count());
        $this->assertFalse($search->current());
        $this->assertFalse($search->key());
        $this->assertFalse($search->valid());
        $search->next();
        $this->assertFalse($search->current());
        $this->assertFalse($search->key());
        $this->assertFalse($search->valid());

        /* Search and simple iterate through the test entries.  Then, rewind
         * and do it again several times. */
        $search2 = $ldap->search(null, '(ou=Horde_Ldap*)');
        $this->assertType('Horde_Ldap_Search', $search2);
        $this->assertEquals(2, $search2->count());
        for ($i = 0; $i <= 5; $i++) {
            $counter = 0;
            foreach ($search2 as $dn => $entry) {
                $counter++;
                // Check on type.
                $this->assertType('Horde_Ldap_Entry', $entry);
                // Check on key.
                $this->assertThat(strlen($dn), $this->greaterThan(1));
                $this->assertEquals($dn, $entry->dn());
            }
            $this->assertEquals($search2->count(), $counter, "Failed at loop $i");

            // Revert to start.
            $search2->rewind();
        }
    }
}
