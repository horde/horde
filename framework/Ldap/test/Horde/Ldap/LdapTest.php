<?php

require_once dirname(__FILE__) . '/TestBase.php';

/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_LdapTest extends Horde_Ldap_TestBase
{
    public static function tearDownAfterClass()
    {
        if (!self::$ldapcfg) {
            return;
        }

        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $clean = array('cn=Horde_Ldap_TestEntry,',
                       'ou=Horde_Ldap_Test_subdelete,',
                       'ou=Horde_Ldap_Test_modify,',
                       'ou=Horde_Ldap_Test_search1,',
                       'ou=Horde_Ldap_Test_search2,',
                       'ou=Horde_Ldap_Test_exists,',
                       'ou=Horde_Ldap_Test_getEntry,',
                       'ou=Horde_Ldap_Test_move,',
                       'ou=Horde_Ldap_Test_pool,',
                       'ou=Horde_Ldap_Test_tgt,');
        foreach ($clean as $dn) {
            try {
                $ldap->delete($dn . self::$ldapcfg['server']['basedn'], true);
            } catch (Exception $e) {}
        }
    }

    /**
     * Tests if the server can connect and bind correctly.
     */
    public function testConnectAndPrivilegedBind()
    {
        // This connect is supposed to fail.
        $lcfg = array('hostspec' => 'nonexistant.ldap.horde.org');
        try {
            $ldap = new Horde_Ldap($lcfg);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Failing with multiple hosts.
        $lcfg = array('hostspec' => array('nonexistant1.ldap.horde.org',
                                          'nonexistant2.ldap.horde.org'));
        try {
            $ldap = new Horde_Ldap($lcfg);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Simple working connect and privileged bind.
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Working connect and privileged bind with first host down.
        $lcfg = array('hostspec' => array('nonexistant.ldap.horde.org',
                                          self::$ldapcfg['server']['hostspec']),
                      'port'      => self::$ldapcfg['server']['port'],
                      'binddn'    => self::$ldapcfg['server']['binddn'],
                      'bindpw'    => self::$ldapcfg['server']['bindpw']);
        $ldap = new Horde_Ldap($lcfg);
    }

    /**
     * Tests if the server can connect and bind anonymously, if supported.
     */
    public function testConnectAndAnonymousBind()
    {
        if (!self::$ldapcfg['capability']['anonymous']) {
            $this->markTestSkipped('Server does not support anonymous bind');
        }

        // Simple working connect and anonymous bind.
        $lcfg = array('hostspec' => self::$ldapcfg['server']['hostspec'],
                      'port'     => self::$ldapcfg['server']['port']);
        $ldap = new Horde_Ldap($lcfg);
    }

    /**
     * Tests startTLS() if server supports it.
     */
    public function testStartTLS()
    {
        if (!self::$ldapcfg['capability']['tls']) {
            $this->markTestSkipped('Server does not support TLS');
        }

        // Simple working connect and privileged bind.
        $lcfg = array('starttls' => true) + self::$ldapcfg['server'];
        $ldap = new Horde_Ldap($lcfg);
    }

    /**
     * Test if adding and deleting a fresh entry works.
     */
    public function testAdd()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Adding a fresh entry.
        $cn = 'Horde_Ldap_TestEntry';
        $dn = 'cn=' . $cn . ',' . self::$ldapcfg['server']['basedn'];
        $fresh_entry = Horde_Ldap_Entry::createFresh(
            $dn,
            array('objectClass' => array('top', 'person'),
                  'cn'          => $cn,
                  'sn'          => 'TestEntry'));
        $this->assertType('Horde_Ldap_Entry', $fresh_entry);
        $ldap->add($fresh_entry);

        // Deleting this entry.
        $ldap->delete($fresh_entry);
    }

    /**
     * Basic deletion is tested in testAdd(), so here we just test if
     * advanced deletion tasks work properly.
     */
    public function testDelete()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Some parameter checks.
        try {
            $ldap->delete(1234);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
        try {
            $ldap->delete($ldap);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // In order to test subtree deletion, we need some little tree
        // which we need to establish first.
        $base   = self::$ldapcfg['server']['basedn'];
        $testdn = 'ou=Horde_Ldap_Test_subdelete,' . $base;

        $ou = Horde_Ldap_Entry::createFresh(
            $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_subdelete'));
        $ou_1 = Horde_Ldap_Entry::createFresh(
            'ou=test1,' . $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'test1'));
        $ou_1_l1 = Horde_Ldap_Entry::createFresh(
            'l=subtest,ou=test1,' . $testdn,
            array('objectClass' => array('top', 'locality'),
                  'l' => 'test1'));
        $ou_2 = Horde_Ldap_Entry::createFresh(
            'ou=test2,' . $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'test2'));
        $ou_3 = Horde_Ldap_Entry::createFresh(
            'ou=test3,' . $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'test3'));
        $ldap->add($ou);
        $ldap->add($ou_1);
        $ldap->add($ou_1_l1);
        $ldap->add($ou_2);
        $ldap->add($ou_3);
        $this->assertTrue($ldap->exists($ou->dn()));
        $this->assertTrue($ldap->exists($ou_1->dn()));
        $this->assertTrue($ldap->exists($ou_1_l1->dn()));
        $this->assertTrue($ldap->exists($ou_2->dn()));
        $this->assertTrue($ldap->exists($ou_3->dn()));
        // Tree established now. We can run some tests now :D

        // Try to delete some non existent entry inside that subtree (fails).
        try {
            $ldap->delete('cn=not_existent,ou=test1,' . $testdn);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {
            $this->assertEquals('LDAP_NO_SUCH_OBJECT', Horde_Ldap::errorName($e->getCode()));
        }

        // Try to delete main test ou without recursive set (fails too).
        try {
            $ldap->delete($testdn);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {
            $this->assertEquals('LDAP_NOT_ALLOWED_ON_NONLEAF', Horde_Ldap::errorName($e->getCode()));
        }

        // Retry with subtree delete, this should work.
        $ldap->delete($testdn, true);

        // The DN is not allowed to exist anymore.
        $this->assertFalse($ldap->exists($testdn));
    }

    /**
     * Test modify().
     */
    public function testModify()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // We need a test entry.
        $local_entry = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_modify,' . self::$ldapcfg['server']['basedn'],
            array('objectClass'     => array('top', 'organizationalUnit'),
                  'ou'              => 'Horde_Ldap_Test_modify',
                  'street'          => 'Beniroad',
                  'telephoneNumber' => array('1234', '5678'),
                  'postalcode'      => '12345',
                  'postalAddress'   => 'someAddress',
                  'st'              => array('State 1', 'State 2')));
        $ldap->add($local_entry);
        $this->assertTrue($ldap->exists($local_entry->dn()));

        // Prepare some changes.
        $changes = array(
            'add' => array(
                'businessCategory' => array('foocat', 'barcat'),
                'description' => 'testval'
            ),
            'delete' => array('postalAddress'),
            'replace' => array('telephoneNumber' => array('345', '567')),
            'changes' => array(
                'replace' => array('street' => 'Highway to Hell'),
                'add' => array('l' => 'someLocality'),
                'delete' => array(
                    'postalcode',
                    'st' => array('State 1'))));

        // Perform those changes.
        $ldap->modify($local_entry, $changes);

        // Verify correct attribute changes.
        $actual_entry = $ldap->getEntry($local_entry->dn(),
                                        array('objectClass', 'ou',
                                              'postalAddress', 'street',
                                              'telephoneNumber', 'postalcode',
                                              'st', 'l', 'businessCategory',
                                              'description'));
        $this->assertType('Horde_Ldap_Entry', $actual_entry);
        $expected_attributes = array(
            'objectClass'      => array('top', 'organizationalUnit'),
            'ou'               => 'Horde_Ldap_Test_modify',
            'street'           => 'Highway to Hell',
            'l'                => 'someLocality',
            'telephoneNumber'  => array('345', '567'),
            'businessCategory' => array('foocat', 'barcat'),
            'description'      => 'testval',
            'st'               => 'State 2'
        );

        $local_attributes  = $local_entry->getValues();
        $actual_attributes = $actual_entry->getValues();

        // To enable easy check, we need to sort the values of the remaining
        // multival attributes as well as the attribute names.
        ksort($expected_attributes);
        ksort($local_attributes);
        ksort($actual_attributes);
        sort($expected_attributes['businessCategory']);
        sort($local_attributes['businessCategory']);
        sort($actual_attributes['businessCategory']);

        // The attributes must match the expected values.  Both, the entry
        // inside the directory and our local copy must reflect the same
        // values.
        $this->assertEquals($expected_attributes, $actual_attributes, 'The directory entries attributes are not OK!');
        $this->assertEquals($expected_attributes, $local_attributes, 'The local entries attributes are not OK!');
    }

    /**
     * Test search().
     */
    public function testSearch()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Some testdata, so we can test sizelimit.
        $base = self::$ldapcfg['server']['basedn'];
        $ou1 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_search1,' . $base,
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_search1'));
        $ou1_1 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_search1_1,' . $ou1->dn(),
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_search2'));
        $ou2 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_search2,' . $base,
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_search2'));
        $ldap->add($ou1);
        $this->assertTrue($ldap->exists($ou1->dn()));
        $ldap->add($ou1_1);
        $this->assertTrue($ldap->exists($ou1_1->dn()));
        $ldap->add($ou2);
        $this->assertTrue($ldap->exists($ou2->dn()));


        // Search for test filter, should at least return our two test entries.
        $res = $ldap->search(null, '(ou=Horde_Ldap*)',
                             array('attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertThat($res->count(), $this->greaterThanOrEqual(2));

        // Same, but with Horde_Ldap_Filter object.
        $filtero = Horde_Ldap_Filter::create('ou', 'begins', 'Horde_Ldap');
        $this->assertType('Horde_Ldap_Filter', $filtero);
        $res = $ldap->search(null, $filtero,
                             array('attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertThat($res->count(), $this->greaterThanOrEqual(2));

        // Search using default filter for base-onelevel scope, should at least
        // return our two test entries.
        $res = $ldap->search(null, null,
                             array('scope' => 'one', 'attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertThat($res->count(), $this->greaterThanOrEqual(2));

        // Base-search using custom base (string), should only return the test
        // entry $ou1 and not the entry below it.
        $res = $ldap->search($ou1->dn(), null,
                             array('scope' => 'base', 'attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertEquals(1, $res->count());

        // Search using custom base, this time using an entry object.  This
        // tests if passing an entry object as base works, should only return
        // the test entry $ou1.
        $res = $ldap->search($ou1, '(ou=*)',
                             array('scope' => 'base', 'attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertEquals(1, $res->count());

        // Search using default filter for base-onelevel scope with sizelimit,
        // should of course return more than one entry, but not more than
        // sizelimit
        $res = $ldap->search(
            null, null,
            array('scope' => 'one', 'sizelimit' => 1, 'attributes' => '1.1')
        );
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertEquals(1, $res->count());
        // Sizelimit should be exceeded now.
        $this->assertTrue($res->sizeLimitExceeded());

        // Bad filter.
        try {
            $res = $ldap->search(null, 'somebadfilter',
                                 array('attributes' => '1.1'));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Bad base.
        try {
            $res = $ldap->search('badbase', null,
                                 array('attributes' => '1.1'));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Nullresult.
        $res = $ldap->search(null, '(cn=nevermatching_filter)',
                             array('scope' => 'base', 'attributes' => '1.1'));
        $this->assertType('Horde_Ldap_Search', $res);
        $this->assertEquals(0, $res->count());
    }

    /**
     * Test exists().
     */
    public function testExists()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        $dn = 'ou=Horde_Ldap_Test_exists,' . self::$ldapcfg['server']['basedn'];

        // Testing not existing DN.
        $this->assertFalse($ldap->exists($dn));

        // Passing an entry object (should work). It should return false,
        // because we didn't add the test entry yet.
        $ou1 = Horde_Ldap_Entry::createFresh(
            $dn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_search1'));
        $this->assertFalse($ldap->exists($ou1));

        // Testing not existing DN.
        $ldap->add($ou1);
        $this->assertTrue($ldap->exists($dn));

        // Passing an float instead of a string.
        try {
            $ldap->exists(1.234);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }

    /**
     * Test getEntry().
     */
    public function testGetEntry()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $dn = 'ou=Horde_Ldap_Test_getEntry,' . self::$ldapcfg['server']['basedn'];
        $entry = Horde_Ldap_Entry::createFresh(
            $dn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_getEntry'));
        $ldap->add($entry);

        // Existing DN.
        $this->assertType('Horde_Ldap_Entry', $ldap->getEntry($dn));

        // Not existing DN.
        try {
            $ldap->getEntry('cn=notexistent,' . self::$ldapcfg['server']['basedn']);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Exception_NotFound $e) {}
    }

    /**
     * Test move().
     */
    public function testMove()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // For Moving tests, we need some little tree again.
        $base   = self::$ldapcfg['server']['basedn'];
        $testdn = 'ou=Horde_Ldap_Test_move,' . $base;

        $ou = Horde_Ldap_Entry::createFresh(
            $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_move'));
        $ou_1 = Horde_Ldap_Entry::createFresh(
            'ou=source,' . $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'source'));
        $ou_1_l1 = Horde_Ldap_Entry::createFresh(
            'l=moveitem,ou=source,' . $testdn,
            array('objectClass' => array('top','locality'),
                  'l' => 'moveitem',
                  'description' => 'movetest'));
        $ou_2 = Horde_Ldap_Entry::createFresh(
            'ou=target,' . $testdn,
            array('objectClass' => array('top', 'organizationalUnit'),
                  'ou' => 'target'));
        $ou_3 = Horde_Ldap_Entry::createFresh(
            'ou=target_otherdir,' . $testdn,
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'target_otherdir'));
        $ldap->add($ou);
        $ldap->add($ou_1);
        $ldap->add($ou_1_l1);
        $ldap->add($ou_2);
        $ldap->add($ou_3);
        $this->assertTrue($ldap->exists($ou->dn()));
        $this->assertTrue($ldap->exists($ou_1->dn()));
        $this->assertTrue($ldap->exists($ou_1_l1->dn()));
        $this->assertTrue($ldap->exists($ou_2->dn()));
        $this->assertTrue($ldap->exists($ou_3->dn()));
        // Tree established.

        // Local rename.
        $olddn = $ou_1_l1->currentDN();
        $ldap->move($ou_1_l1, str_replace('moveitem', 'move_item', $ou_1_l1->dn()));
        $this->assertTrue($ldap->exists($ou_1_l1->dn()));
        $this->assertFalse($ldap->exists($olddn));

        // Local move.
        $olddn = $ou_1_l1->currentDN();
        $ldap->move($ou_1_l1, 'l=move_item,' . $ou_2->dn());
        $this->assertTrue($ldap->exists($ou_1_l1->dn()));
        $this->assertFalse($ldap->exists($olddn));

        // Local move backward, with rename. Here we use the DN of the object,
        // to test DN conversion.
        // Note that this will outdate the object since it does not has
        // knowledge about the move.
        $olddn = $ou_1_l1->currentDN();
        $newdn = 'l=moveditem,' . $ou_2->dn();
        $ldap->move($olddn, $newdn);
        $this->assertTrue($ldap->exists($newdn));
        $this->assertFalse($ldap->exists($olddn));
        // Refetch since the object's DN was outdated.
        $ou_1_l1 = $ldap->getEntry($newdn);

        // Fake-cross directory move using two separate links to the same
        // directory. This other directory is represented by
        // ou=target_otherdir.
        $ldap2 = new Horde_Ldap(self::$ldapcfg['server']);
        $olddn = $ou_1_l1->currentDN();
        $ldap->move($ou_1_l1, 'l=movedcrossdir,' . $ou_3->dn(), $ldap2);
        $this->assertFalse($ldap->exists($olddn));
        $this->assertTrue($ldap2->exists($ou_1_l1->dn()));

        // Try to move over an existing entry.
        try {
            $ldap->move($ou_2, $ou_3->dn(), $ldap2);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Try cross directory move without providing an valid entry but a DN.
        try {
            $ldap->move($ou_1_l1->dn(), 'l=movedcrossdir2,'.$ou_2->dn(), $ldap2);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Try passing an invalid entry object.
        try {
            $ldap->move($ldap, 'l=move_item,'.$ou_2->dn());
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Try passing an invalid LDAP object.
        try {
            $ldap->move($ou_1_l1, 'l=move_item,'.$ou_2->dn(), $ou_1);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }

    /**
     * Test copy().
     */
    public function testCopy()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);

        // Some testdata.
        $base = self::$ldapcfg['server']['basedn'];
        $ou1 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_pool,' . $base,
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_copy'));
        $ou2 = Horde_Ldap_Entry::createFresh(
            'ou=Horde_Ldap_Test_tgt,' . $base,
            array('objectClass' => array('top','organizationalUnit'),
                  'ou' => 'Horde_Ldap_Test_copy'));
        $ldap->add($ou1);
        $this->assertTrue($ldap->exists($ou1->dn()));
        $ldap->add($ou2);
        $this->assertTrue($ldap->exists($ou2->dn()));

        $entry = Horde_Ldap_Entry::createFresh(
            'l=cptest,' . $ou1->dn(),
            array('objectClass' => array('top','locality'),
                  'l' => 'cptest'));
        $ldap->add($entry);
        $ldap->exists($entry->dn());

        // Copy over the entry to another tree with rename.
        $entrycp = $ldap->copy($entry, 'l=test_copied,' . $ou2->dn());
        $this->assertType('Horde_Ldap_Entry', $entrycp);
        $this->assertNotEquals($entry->dn(), $entrycp->dn());
        $this->assertTrue($ldap->exists($entrycp->dn()));

        // Copy same again (fails, entry exists).
        try {
            $entrycp_f = $ldap->copy($entry, 'l=test_copied,' . $ou2->dn());
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Use only DNs to copy (fails).
        try {
            $entrycp = $ldap->copy($entry->dn(), 'l=test_copied2,' . $ou2->dn());
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }

    /**
     * Tests retrieval of root DSE object.
     */
    public function testRootDSE()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $this->assertType('Horde_Ldap_RootDse', $ldap->rootDSE());
    }

    /**
     * Tests retrieval of schema through LDAP object.
     */
    public function testSchema()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $this->assertType('Horde_Ldap_Schema', $ldap->schema());
    }

    /**
     * Test getLink().
     */
    public function testGetLink()
    {
        $ldap = new Horde_Ldap(self::$ldapcfg['server']);
        $this->assertTrue(is_resource($ldap->getLink()));
    }
}
