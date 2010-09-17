<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_LdifTest extends PHPUnit_Framework_TestCase
{
    /**
     * Default configuration for tests.
     *
     * The config is bound to the ldif test file
     * tests/fixtures/unsorted_w50.ldif, so don't change or tests will fail.
     *
     * @var array
     */
    protected $_defaultConfig = array(
        'encode'  => 'base64',
        'wrap'    => 50,
        'change'  => 0,
        'sort'    => 0,
        'version' => 1
    );

    /**
     * Test entries data.
     *
     * Please do not just modify these values, they are closely related to the
     * LDIF test data.
     *
     * @var array
     */
    protected $_testdata = array(
        'cn=test1,ou=example,dc=cno' => array(
            'cn'          => 'test1',
            'attr3'       => array('foo', 'bar'),
            'attr1'       => 12345,
            'attr4'       => 'brrrzztt',
            'objectclass' => 'oc1',
            'attr2'       => array('1234', 'baz')),

        'cn=test blabla,ou=example,dc=cno' => array(
            'cn'          => 'test blabla',
            'attr3'       => array('foo', 'bar'),
            'attr1'       => 12345,
            'attr4'       => 'blablaöäü',
            'objectclass' => 'oc2',
            'attr2'       => array('1234', 'baz'),
            'verylong'    => 'fhu08rhvt7b478vt5hv78h45nfgt45h78t34hhhhhhhhhv5bg8h6ttttttttt3489t57nhvgh4788trhg8999vnhtgthgui65hgb5789thvngwr789cghm738'),

        'cn=test öäü,ou=example,dc=cno' => array(
            'cn'          => 'test öäü',
            'attr3'       => array('foo', 'bar'),
            'attr1'       => 12345,
            'attr4'       => 'blablaöäü',
            'objectclass' => 'oc3',
            'attr2'       => array('1234', 'baz'),
            'attr5'       => 'endspace ',
            'attr6'       => ':badinitchar'),

        ':cn=endspace,dc=cno ' => array(
            'cn'          => 'endspace')
    );

    /**
     * Test file written to.
     *
     * @var string
     */
    protected $_outfile = 'test.out.ldif';

    /**
     * Test entries.
     *
     * They will be created in setUp()
     *
     * @var array
     */
    protected $_testentries;

    /**
     * Opens an outfile and ensures correct permissions.
     */
    public function setUp()
    {
        // Initialize test entries.
        $this->_testentries = array();
        foreach ($this->_testdata as $dn => $attrs) {
            $entry = Horde_Ldap_Entry::createFresh($dn, $attrs);
            $this->assertType('Horde_Ldap_Entry', $entry);
            array_push($this->_testentries, $entry);
        }

        // Create outfile if not exists and enforce proper access rights.
        if (!file_exists($this->_outfile)) {
            if (!touch($this->_outfile)) {
                $this->markTestSkipped('Unable to create ' . $this->_outfile);
            }
        }
        if (!chmod($this->_outfile, 0644)) {
            $this->markTestSkipped('Unable to chmod(0644) ' . $this->_outfile);
        }
    }

    /**
     * Removes the outfile.
     */
    public function tearDown() {
        @unlink($this->_outfile);
    }

    /**
     * Construction tests.
     *
     * Construct LDIF object and see if we can get a handle.
     */
    public function testConstruction()
    {
        $supported_modes = array('r', 'w', 'a');
        $plus            = array('', '+');

        // Test all open modes, all of them should return a correct handle.
        foreach ($supported_modes as $mode) {
            foreach ($plus as $p) {
                $ldif = new Horde_Ldap_Ldif($this->_outfile, $mode, $this->_defaultConfig);
                $this->assertTrue(is_resource($ldif->handle()));
            }
        }

        // Test illegal option passing.
        try {
            $ldif = new Horde_Ldap_Ldif($this->_outfile, $mode, array('somebad' => 'option'));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Test passing custom handle.
        $handle = fopen($this->_outfile, 'r');
        $ldif = new Horde_Ldap_Ldif($handle, $mode, $this->_defaultConfig);
        $this->assertTrue(is_resource($ldif->handle()));

        // Reading test with invalid file mode.
        try {
            $ldif = new Horde_Ldap_Ldif($this->_outfile, 'y', $this->_defaultConfig);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Reading test with non-existent file.
        try {
            $ldif = new Horde_Ldap_Ldif('some/nonexistent/file_for_net_ldap_ldif', 'r', $this->_defaultConfig);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Writing to non-existent file.
        $ldif = new Horde_Ldap_Ldif('testfile_for_net_ldap_ldif', 'w', $this->_defaultConfig);
        $this->assertTrue(is_resource($ldif->handle()));
        @unlink('testfile_for_net_ldap_ldif');

        // Writing to non-existent path.
        try {
            $ldif = new Horde_Ldap_Ldif('some/nonexistent/file_for_net_ldap_ldif', 'w', $this->_defaultConfig);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        // Writing to existing file but without permission. chmod() should
        // succeed since we test that in setUp().
        if (chmod($this->_outfile, 0444)) {
            try {
                $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $this->_defaultConfig);
                $this->fail('Horde_Ldap_Exception expected.');
            } catch (Horde_Ldap_Exception $e) {}
        } else {
            $this->markTestSkipped('Could not chmod ' . $this->_outfile . ', write test without permission skipped');
        }
    }

    /**
     * Tests if entries from an LDIF file are correctly constructed.
     */
    public function testReadEntry()
    {
        /* UNIX line endings. */
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50.ldif', 'r', $this->_defaultConfig);
        $this->assertTrue(is_resource($ldif->handle()));

        $entries = array();
        do {
            $entry = $ldif->readEntry();
            $this->assertType('Horde_Ldap_Entry', $entry);
            array_push($entries, $entry);
        } while (!$ldif->eof());

        $this->_compareEntries($this->_testentries, $entries);

        /* Windows line endings. */
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50_WIN.ldif', 'r', $this->_defaultConfig);
        $this->assertTrue(is_resource($ldif->handle()));

        $entries = array();
        do {
            $entry = $ldif->readEntry();
            $this->assertType('Horde_Ldap_Entry', $entry);
            array_push($entries, $entry);
        } while (!$ldif->eof());

        $this->_compareEntries($this->_testentries, $entries);
    }

    /**
     * Tests if entries are correctly written.
     *
     * This tests converting entries to LDIF lines, wrapping, encoding, etc.
     */
    public function testWriteEntry()
    {
        $testconf = $this->_defaultConfig;

        /* Test wrapped operation. */
        $testconf['wrap'] = 50;
        $testconf['sort'] = 0;
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/unsorted_w50.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files.
        $this->assertEquals($expected, file($this->_outfile));

        $testconf['wrap'] = 30;
        $testconf['sort'] = 0;
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/unsorted_w30.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files.
        $this->assertEquals($expected, file($this->_outfile));

        /* Test unwrapped operation. */
        $testconf['wrap'] = 40;
        $testconf['sort'] = 1;
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/sorted_w40.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files.
        $this->assertEquals($expected, file($this->_outfile));

        $testconf['wrap'] = 50;
        $testconf['sort'] = 1;
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/sorted_w50.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files.
        $this->assertEquals($expected, file($this->_outfile));

        /* Test raw option. */
        $testconf['wrap'] = 50;
        $testconf['sort'] = 1;
        $testconf['raw']  = '/attr6/';
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/sorted_w50.ldif'));
        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files, with expected attributes adjusted.
        $this->assertEquals($expected, file($this->_outfile));

        /* Test writing with non entry as parameter. */
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w');
        $this->assertTrue(is_resource($ldif->handle()));
        try {
            $ldif->writeEntry('malformed_parameter');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }

    /**
     * Test version writing.
     */
    public function testWriteVersion()
    {
        $testconf = $this->_defaultConfig;

        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/unsorted_w50.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        // Strip 1 additional line (the "version: 1" line that should not be
        // written now) and adjust test config.
        array_shift($expected);
        unset($testconf['version']);

        // Write LDIF.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($this->_testentries);
        $ldif->done();

        // Compare files.
        $this->assertEquals($expected, file($this->_outfile));
    }

    /**
     * Round trip test: Read LDIF, parse to entries, write that to LDIF and
     * compare both files.
     */
    public function testReadWriteRead()
    {
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50.ldif', 'r', $this->_defaultConfig);
        $this->assertTrue(is_resource($ldif->handle()));

        // Read LDIF.
        $entries = array();
        do {
            $entry = $ldif->readEntry();
            $this->assertType('Horde_Ldap_Entry', $entry);
            array_push($entries, $entry);
        } while (!$ldif->eof());
        $ldif->done();

         // Write LDIF.
         $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $this->_defaultConfig);
         $this->assertTrue(is_resource($ldif->handle()));
         $ldif->writeEntry($entries);
         $ldif->done();

         // Compare files.
         $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/unsorted_w50.ldif'));

         // Strip 4 starting lines because of comments in the file header.
         array_splice($expected, 0, 4);

         $this->assertEquals($expected, file($this->_outfile));
    }

    /**
     * Tests if entry changes are correctly written.
     */
    public function testWriteEntryChanges()
    {
        $testentries = $this->_testentries;
        $testentries[] = Horde_Ldap_Entry::createFresh('cn=foo,ou=example,dc=cno', array('cn' => 'foo'));
        $testentries[] = Horde_Ldap_Entry::createFresh('cn=footest,ou=example,dc=cno', array('cn' => 'foo'));

        $testconf = $this->_defaultConfig;
        $testconf['change'] = 1;

        /* No changes should produce empty file. */
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($testentries);
        $ldif->done();
        $this->assertEquals(array(), file($this->_outfile));

        /* Changes test. */
        // Prepare some changes.
        $testentries[0]->delete('attr1');
        $testentries[0]->delete(array('attr2' => 'baz'));
        $testentries[0]->delete(array('attr4', 'attr3' => 'bar'));

        // Prepare some replaces and adds.
        $testentries[2]->replace(array('attr1' => 'newvaluefor1'));
        $testentries[2]->replace(array('attr2' => array('newvalue1for2', 'newvalue2for2')));
        $testentries[2]->replace(array('attr3' => ''));
        $testentries[2]->replace(array('newattr' => 'foo'));

        // Delete whole entry.
        $testentries[3]->delete();

        // Rename and move.
        $testentries[4]->dn('cn=Bar,ou=example,dc=cno');
        $testentries[5]->dn('cn=foobartest,ou=newexample,dc=cno');

        // Carry out write.
        $ldif = new Horde_Ldap_Ldif($this->_outfile, 'w', $testconf);
        $this->assertTrue(is_resource($ldif->handle()));
        $ldif->writeEntry($testentries);
        $ldif->done();

        // Compare results.
        $expected = array_map(array($this, '_lineend'), file(dirname(__FILE__).'/fixtures/changes.ldif'));

        // Strip 4 starting lines because of comments in the file header.
        array_splice($expected, 0, 4);

        $this->assertEquals($expected, file($this->_outfile));
    }

    /**
     * Tests if syntax errors are detected.
     *
     * The used LDIF files have several damaged entries but always one
     * correct too, to test if Horde_Ldap_Ldif is continue reading as it should
     * each entry must have 2 correct attributes.
     */
    public function testSyntaxerrors()
    {
        $this->markTestSkipped('We don\'t continue on syntax errors.');
        // Test malformed encoding
        // I think we can ignore this test, because if the LDIF is not encoded properly, we
        // might be able to successfully fetch the entries data. However, it is possible
        // that it will be corrupted, but thats not our fault then.
        // If we should catch that error, we must adjust Horde_Ldap_Ldif::next_lines().
        /*
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/malformed_encoding.ldif', 'r', $this->_defaultConfig);
        $this->assertFalse((boolean)$ldif->error());
        $entries = array();
        do {
            $entry = $ldif->readEntry();
            if ($entry) {
                // the correct attributes need to be parsed
                $this->assertThat(count(array_keys($entry->getValues())), $this->equalTo(2));
                $entries[] = $entry;
            }
        } while (!$ldif->eof());
        $this->assertTrue((boolean)$ldif->error());
        $this->assertThat($ldif->error_lines(), $this->greaterThan(1));
        $this->assertThat(count($entries), $this->equalTo(1));
        */

        // Test malformed syntax
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/malformed_syntax.ldif', 'r', $this->_defaultConfig);
        $this->assertFalse((boolean)$ldif->error());
        $entries = array();
        do {
            $entry = $ldif->readEntry();
            if ($entry) {
                // the correct attributes need to be parsed
                $this->assertThat(count(array_keys($entry->getValues())), $this->equalTo(2));
                $entries[] = $entry;
            }
        } while (!$ldif->eof());
        $this->assertTrue((boolean)$ldif->error());
        $this->assertThat($ldif->error_lines(), $this->greaterThan(1));
        $this->assertThat(count($entries), $this->equalTo(2));

        // test bad wrapping
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/malformed_wrapping.ldif', 'r', $this->_defaultConfig);
        $this->assertFalse((boolean)$ldif->error());
        $entries = array();
        do {
           $entry = $ldif->readEntry();
            if ($entry) {
                // the correct attributes need to be parsed
                $this->assertThat(count(array_keys($entry->getValues())), $this->equalTo(2));
                $entries[] = $entry;
            }
        } while (!$ldif->eof());
        $this->assertTrue((boolean)$ldif->error());
        $this->assertThat($ldif->error_lines(), $this->greaterThan(1));
        $this->assertThat(count($entries), $this->equalTo(2));
    }

    /**
     * Test error dropping functionality.
     */
    public function testError()
    {
        $this->markTestSkipped('We use exceptions, not the original error handling.');

        // No error.
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50.ldif', 'r', $this->_defaultConfig);

        // Error giving error msg and line number:
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/some_not_existing/path/for/net_ldap_ldif', 'r', $this->_defaultConfig);
        $this->assertTrue((boolean)$ldif->error());
        $this->assertType('Net_LDAP2_Error', $ldif->error());
        $this->assertType('string', $ldif->error(true));
        $this->assertType('int', $ldif->error_lines());
        $this->assertThat(strlen($ldif->error(true)), $this->greaterThan(0));

        // Test for line number reporting
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/malformed_syntax.ldif', 'r', $this->_defaultConfig);
        $this->assertFalse((boolean)$ldif->error());
        do { $entry = $ldif->readEntry(); } while (!$ldif->eof());
        $this->assertTrue((boolean)$ldif->error());
        $this->assertThat($ldif->error_lines(), $this->greaterThan(1));
    }

    /**
     * Tests currentLines() and nextLines().
     *
     * This should always return the same lines unless forced.
     */
    public function testLineMethods()
    {
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50.ldif', 'r', $this->_defaultConfig);
        $this->assertEquals(array(), $ldif->currentLines(), 'Horde_Ldap_Ldif initialization error!');

        // Read first lines.
        $lines = $ldif->nextLines();

        // Read the first lines several times and test.
        for ($i = 0; $i <= 10; $i++) {
            $r_lines = $ldif->nextLines();
            $this->assertEquals($lines, $r_lines);
        }

        // Now force to iterate and see if the content changes.
        $r_lines = $ldif->nextLines(true);
        $this->assertNotEquals($lines, $r_lines);

        // It could be confusing to some people, but calling currentEntry()
        // would not work now, like the description of the method says.
        $no_entry = $ldif->currentLines();
        $this->assertEquals(array(), $no_entry);
    }

    /**
     * Tests currentEntry(). This should always return the same object.
     */
    public function testcurrentEntry()
    {
        $ldif = new Horde_Ldap_Ldif(dirname(__FILE__).'/fixtures/unsorted_w50.ldif', 'r', $this->_defaultConfig);

        // Read first entry.
        $entry = $ldif->readEntry();

        // Test if currentEntry remains the first one.
        for ($i = 0; $i <= 10; $i++) {
            $e = $ldif->currentEntry();
            $this->assertEquals($entry, $e);
        }
    }

    /**
     * Compares two Horde_Ldap_Entries.
    *
    * This helper function compares two entries (or array of entries) and
    * checks if they are equal. They are equal if all DNs from the first crowd
    * exist in the second AND each attribute is present and equal at the
    * respective entry. The search is case sensitive.
    *
    * @param array|Horde_Ldap_Entry $entry1
    * @param array|Horde_Ldap_Entry $entry2
    * @return boolean
    */
    protected function _compareEntries($entry1, $entry2)
    {
        if (!is_array($entry1)) {
            $entry1 = array($entry1);
        }
        if (!is_array($entry2)) {
            $entry2 = array($entry2);
        }

        $entries_data1 = $entries_data2  = array();

        // Step 1: extract and sort data.
        foreach ($entry1 as $e) {
            $values = $e->getValues();
            foreach ($values as $attr_name => $attr_values) {
                if (!is_array($attr_values)) {
                    $attr_values = array($attr_values);
                }
                $values[$attr_name] = $attr_values;
            }
            $entries_data1[$e->dn()] = $values;
        }
        foreach ($entry2 as $e) {
            $values = $e->getValues();
            foreach ($values as $attr_name => $attr_values) {
                if (!is_array($attr_values)) {
                    $attr_values = array($attr_values);
                }
                $values[$attr_name] = $attr_values;
            }
            $entries_data2[$e->dn()] = $values;
        }

        // Step 2: compare DNs (entries).
        $this->assertEquals(array_keys($entries_data1), array_keys($entries_data2), 'Entries DNs not equal! (missing entry or wrong DN)');

        // Step 3: look for attribute existence and compare values.
        foreach ($entries_data1 as $dn => $attributes) {
            $this->assertEquals($entries_data1[$dn], $entries_data2[$dn], 'Entries ' . $dn . ' attributes are not equal');
            foreach ($attributes as $attr_name => $attr_values) {
                $this->assertEquals(0, count(array_diff($entries_data1[$dn][$attr_name], $entries_data2[$dn][$attr_name])), 'Entries ' . $dn . ' attribute ' . $attr_name . ' values are not equal');
            }
        }

        return true;
    }

    /**
     * Create line endings for current OS.
     *
     * This is neccessary to make write tests platform indendent.
     *
     * @param string $line Line
     * @return string
     */
    protected function _lineend($line)
    {
        return rtrim($line) . PHP_EOL;
    }
}
