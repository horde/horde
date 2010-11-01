<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

class Horde_Ldap_FilterTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test correct parsing of filter strings through parse().
     */
    public function testParse()
    {
        try {
            Horde_Ldap_Filter::parse('some_damaged_filter_str');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::parse('(invalid=filter)(because=~no-surrounding brackets)');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::parse('((invalid=filter)(because=log_op is missing))');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::parse('(invalid-because-becauseinvalidoperator)');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::parse('(&(filterpart>=ok)(part2=~ok)(filterpart3_notok---becauseinvalidoperator))');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        $parsed1 = Horde_Ldap_Filter::parse('(&(cn=foo)(ou=bar))');
        $this->assertType('Horde_Ldap_Filter', $parsed1);
        $this->assertEquals('(&(cn=foo)(ou=bar))', (string)$parsed1);

        // In an earlier version there was a problem with the splitting of the
        // filter parts if the next part was also an combined filter.
        $parsed2_str = '(&(&(objectClass=posixgroup)(objectClass=foogroup))(uniquemember=uid=eeggs,ou=people,o=foo))';
        $parsed2 = Horde_Ldap_Filter::parse($parsed2_str);
        $this->assertType('Horde_Ldap_Filter', $parsed2);
        $this->assertEquals($parsed2_str, (string)$parsed2);

        // In an earlier version there was a problem parsing certain
        // not-combined filter strings.
        $parsed3_str = '(!(jpegPhoto=*))';
        $parsed3 = Horde_Ldap_Filter::parse($parsed3_str);
        $this->assertType('Horde_Ldap_Filter', $parsed3);
        $this->assertEquals($parsed3_str, (string)$parsed3);

        $parsed3_complex_str = '(&(someAttr=someValue)(!(jpegPhoto=*)))';
        $parsed3_complex = Horde_Ldap_Filter::parse($parsed3_complex_str);
        $this->assertType('Horde_Ldap_Filter', $parsed3_complex);
        $this->assertEquals($parsed3_complex_str, (string)$parsed3_complex);
    }

    /**
     * This tests the basic create() method of creating filters.
     */
    public function testCreate()
    {
        // Test values and an array containing the filter creating methods and
        // an regex to test the resulting filter.
        $testattr = 'testattr';
        $testval  = 'testval';
        $combinations = array(
            'equals'         => "/\($testattr=$testval\)/",
            'begins'         => "/\($testattr=$testval\*\)/",
            'ends'           => "/\($testattr=\*$testval\)/",
            'contains'       => "/\($testattr=\*$testval\*\)/",
            'greater'        => "/\($testattr>$testval\)/",
            'less'           => "/\($testattr<$testval\)/",
            'greaterorequal' => "/\($testattr>=$testval\)/",
            'lessorequal'    => "/\($testattr<=$testval\)/",
            'approx'         => "/\($testattr~=$testval\)/",
            'any'            => "/\($testattr=\*\)/"
        );

        foreach ($combinations as $match => $regex) {
            // Escaping is tested in util class.
            $filter = Horde_Ldap_Filter::create($testattr, $match, $testval, false);
            $this->assertType('Horde_Ldap_Filter', $filter);
            $this->assertRegExp($regex, (string)$filter, "Filter generation failed for MatchType: $match");
        }

        // Test creating failure.
        try {
            Horde_Ldap_Filter::create($testattr, 'test_undefined_matchingrule', $testval);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }

    /**
     * Tests if __toString() works.
     */
    public function testToString()
    {
        $filter = Horde_Ldap_Filter::create('foo', 'equals', 'bar');
        $this->assertType('Horde_Ldap_Filter', $filter);
        $this->assertEquals('(foo=bar)', (string)$filter);
    }

    /**
     * This tests the basic combination of filters.
     */
    public function testCombine()
    {
        // Setup.
        $filter0 = Horde_Ldap_Filter::create('foo', 'equals', 'bar');
        $this->assertType('Horde_Ldap_Filter', $filter0);

        $filter1 = Horde_Ldap_Filter::create('bar', 'equals', 'foo');
        $this->assertType('Horde_Ldap_Filter', $filter1);

        $filter2 = Horde_Ldap_Filter::create('you', 'equals', 'me');
        $this->assertType('Horde_Ldap_Filter', $filter2);

        $filter3 = Horde_Ldap_Filter::parse('(perlinterface=used)');
        $this->assertType('Horde_Ldap_Filter', $filter3);

        // Negation test.
        $filter_not1 = Horde_Ldap_Filter::combine('not', $filter0);
        $this->assertType('Horde_Ldap_Filter', $filter_not1, 'Negation failed for literal NOT');
        $this->assertEquals('(!(foo=bar))', (string)$filter_not1);

        $filter_not2 = Horde_Ldap_Filter::combine('!', $filter0);
        $this->assertType('Horde_Ldap_Filter', $filter_not2, 'Negation failed for logical NOT');
        $this->assertEquals('(!(foo=bar))', (string)$filter_not2);

        $filter_not3 = Horde_Ldap_Filter::combine('!', (string)$filter0);
        $this->assertType('Horde_Ldap_Filter', $filter_not3, 'Negation failed for logical NOT');
        $this->assertEquals('(!' . $filter0 . ')', (string)$filter_not3);

        // Combination test: OR
        $filter_comb_or1 = Horde_Ldap_Filter::combine('or', array($filter1, $filter2));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_or1, 'Combination failed for literal OR');
        $this->assertEquals('(|(bar=foo)(you=me))', (string)$filter_comb_or1);

        $filter_comb_or2 = Horde_Ldap_Filter::combine('|', array($filter1, $filter2));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_or2, 'combination failed for logical OR');
        $this->assertEquals('(|(bar=foo)(you=me))', (string)$filter_comb_or2);

        // Combination test: AND
        $filter_comb_and1 = Horde_Ldap_Filter::combine('and', array($filter1, $filter2));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_and1, 'Combination failed for literal AND');
        $this->assertEquals('(&(bar=foo)(you=me))', (string)$filter_comb_and1);

        $filter_comb_and2 = Horde_Ldap_Filter::combine('&', array($filter1, $filter2));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_and2, 'combination failed for logical AND');
        $this->assertEquals('(&(bar=foo)(you=me))', (string)$filter_comb_and2);

        // Combination test: using filter created with perl interface.
        $filter_comb_perl1 = Horde_Ldap_Filter::combine('and', array($filter1, $filter3));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_perl1, 'Combination failed for literal AND');
        $this->assertEquals('(&(bar=foo)(perlinterface=used))', (string)$filter_comb_perl1);

        $filter_comb_perl2 = Horde_Ldap_Filter::combine('&', array($filter1, $filter3));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_perl2, 'combination failed for logical AND');
        $this->assertEquals('(&(bar=foo)(perlinterface=used))', (string)$filter_comb_perl2);

        // Combination test: using filter_str instead of object
        $filter_comb_fstr1 = Horde_Ldap_Filter::combine('and', array($filter1, '(filter_str=foo)'));
        $this->assertType('Horde_Ldap_Filter', $filter_comb_fstr1, 'Combination failed for literal AND using filter_str');
        $this->assertEquals('(&(bar=foo)(filter_str=foo))', (string)$filter_comb_fstr1);

        // Combination test: deep combination
        $filter_comp_deep = Horde_Ldap_Filter::combine('and',array($filter2, $filter_not1, $filter_comb_or1, $filter_comb_perl1));
        $this->assertType('Horde_Ldap_Filter', $filter_comp_deep, 'Deep combination failed!');
        $this->assertEquals('(&(you=me)(!(foo=bar))(|(bar=foo)(you=me))(&(bar=foo)(perlinterface=used)))', (string)$filter_comp_deep);

        // Test failure in combination
        try {
            Horde_Ldap_Filter::create('foo', 'test_undefined_matchingrule', 'bar');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('not', 'damaged_filter_str');
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('not', array($filter0, $filter1));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('not', null);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('and', $filter_not1);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('and', array($filter_not1));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('and', $filter_not1);
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('or', array($filter_not1));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('some_unknown_method', array($filter_not1));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('and', array($filter_not1, 'some_invalid_filterstring'));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}

        try {
            Horde_Ldap_Filter::combine('and', array($filter_not1, null));
            $this->fail('Horde_Ldap_Exception expected.');
        } catch (Horde_Ldap_Exception $e) {}
    }
}
