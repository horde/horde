<?php
/**
 * @package    Ldap
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @copyright  2010 The Horde Project
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL
 */

class Horde_Ldap_UtilTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test escapeDNValue()
     */
    public function testEscape_dn_value()
    {
        $dnval    = '  ' . chr(22) . ' t,e+s"t,\\v<a>l;u#e=!    ';
        $expected = '\20\20\16 t\,e\+s\"t\,\\\\v\<a\>l\;u\#e\=!\20\20\20\20';

        // String call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::escapeDNValue($dnval));

        // Array call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::escapeDNValue(array($dnval)));

        // Multiple arrays.
        $this->assertEquals(
            array($expected, $expected, $expected),
            Horde_Ldap_Util::escapeDNValue(array($dnval, $dnval, $dnval)));
    }

    /**
     * Test unescapeDNValue()
     */
    public function testUnescapeDNValue()
    {
        $dnval    = '\\20\\20\\16\\20t\\,e\\+s \\"t\\,\\\\v\\<a\\>l\\;u\\#e\\=!\\20\\20\\20\\20';
        $expected = '  ' . chr(22) . ' t,e+s "t,\\v<a>l;u#e=!    ';

        // String call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::unescapeDNValue($dnval));

        // Array call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::unescapeDNValue(array($dnval)));

        // Multiple arrays.
        $this->assertEquals(
            array($expected, $expected, $expected),
            Horde_Ldap_Util::unescapeDNValue(array($dnval, $dnval, $dnval)));
    }

    /**
     * Test escaping of filter values.
     */
    public function testEscape_filter_value()
    {
        $expected  = 't\28e,s\29t\2av\5cal\1eue';
        $filterval = 't(e,s)t*v\\al' . chr(30) . 'ue';

        // String call
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::escapeFilterValue($filterval));

        // Array call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::escapeFilterValue(array($filterval)));

        // Multiple arrays.
        $this->assertEquals(
            array($expected, $expected, $expected),
            Horde_Ldap_Util::escapeFilterValue(array($filterval, $filterval, $filterval)));
    }

    /**
     * Test unescaping of filter values.
     */
    public function testUnescapeFilterValue()
    {
        $expected  = 't(e,s)t*v\\al' . chr(30) . 'ue';
        $filterval = 't\28e,s\29t\2av\5cal\1eue';

        // String call
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::unescapeFilterValue($filterval));

        // Array call.
        $this->assertEquals(
            array($expected),
            Horde_Ldap_Util::unescapeFilterValue(array($filterval)));

        // Multiple arrays.
        $this->assertEquals(
            array($expected, $expected, $expected),
            Horde_Ldap_Util::unescapeFilterValue(array($filterval, $filterval, $filterval)));
    }

    /**
     * Test asc2hex32()
     */
    public function testAsc2hex32()
    {
        $expected = '\00\01\02\03\04\05\06\07\08\09\0a\0b\0c\0d\0e\0f\10\11\12\13\14\15\16\17\18\19\1a\1b\1c\1d\1e\1f !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $str = '';
        for ($i = 0; $i < 127; $i++) {
             $str .= chr($i);
        }
        $this->assertEquals($expected, Horde_Ldap_Util::asc2hex32($str));
    }

    /**
     * Test HEX unescaping
     */
    public function testHex2asc()
    {
        $expected = '';
        for ($i = 0; $i < 127; $i++) {
             $expected .= chr($i);
        }
        $str = '\00\01\02\03\04\05\06\07\08\09\0a\0b\0c\0d\0e\0f\10\11\12\13\14\15\16\17\18\19\1a\1b\1c\1d\1e\1f !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~';
        $this->assertEquals($expected, Horde_Ldap_Util::hex2asc($str));
    }

    /**
     * Tests splitRDNMultivalue()
     *
     * In addition to the above test of the basic split correction, we test
     * here the functionality of multivalued RDNs.
     */
    public function testSplit_rdn_multival()
    {
        // One value.
        $rdn = 'CN=J. Smith';
        $expected = array('CN=J. Smith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Two values.
        $rdn = 'OU=Sales+CN=J. Smith';
        $expected = array('OU=Sales', 'CN=J. Smith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Several multivals.
        $rdn = 'OU=Sales+CN=J. Smith+L=London+C=England';
        $expected = array('OU=Sales', 'CN=J. Smith', 'L=London', 'C=England');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Unescaped "+" in value.
        $rdn = 'OU=Sa+les+CN=J. Smith';
        $expected = array('OU=Sa+les', 'CN=J. Smith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Unescaped "+" in attr name.
        $rdn = 'O+U=Sales+CN=J. Smith';
        $expected = array('O+U=Sales', 'CN=J. Smith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Unescaped "+" in attr name + value.
        $rdn = 'O+U=Sales+CN=J. Sm+ith';
        $expected = array('O+U=Sales', 'CN=J. Sm+ith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Unescaped "+" in attribute name, but not first attribute.  This
        // documents a known bug. However, unfortunately we can't know wether
        // the "C+" belongs to value "Sales" or attribute "C+N".  To solve
        // this, we must ask the schema which we do not right now.  The problem
        // is located in _correct_dn_splitting().
        $rdn = 'OU=Sales+C+N=J. Smith';
        // The "C+" is treaten as value of "OU".
        $expected = array('OU=Sales+C', 'N=J. Smith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);

        // Escaped "+" in attribute name and value.
        $rdn = 'O\+U=Sales+CN=J. Sm\+ith';
        $expected = array('O\+U=Sales', 'CN=J. Sm\+ith');
        $split = Horde_Ldap_Util::splitRDNMultivalue($rdn);
        $this->assertEquals($expected, $split);
    }

    /**
     * Tests attribute splitting ('foo=bar' => array('foo', 'bar'))
     */
    public function testSplit_attribute_string()
    {
        $attr_str = 'foo=bar';

        // Properly.
        $expected = array('foo', 'bar');
        $split = Horde_Ldap_Util::splitAttributeString($attr_str);
        $this->assertEquals($expected, $split);

        // Escaped "=".
        $attr_str = "fo\=o=b\=ar";
        $expected = array('fo\=o', 'b\=ar');
        $split = Horde_Ldap_Util::splitAttributeString($attr_str);
        $this->assertEquals($expected, $split);

        // Escaped "=" and unescaped = later on.
        $attr_str = "fo\=o=b=ar";
        $expected = array('fo\=o', 'b=ar');
        $split = Horde_Ldap_Util::splitAttributeString($attr_str);
        $this->assertEquals($expected, $split);
    }

    /**
     * Tests Ldap_explode_dn()
     */
    public function testLdap_explode_dn()
    {
        $dn = 'OU=Sales+CN=J. Smith,dc=example,dc=net';
        $expected_casefold_none = array(
            array('CN=J. Smith', 'OU=Sales'),
            'dc=example',
            'dc=net'
        );
        $expected_casefold_upper = array(
            array('CN=J. Smith', 'OU=Sales'),
            'DC=example',
            'DC=net'
        );
        $expected_casefold_lower = array(
            array('cn=J. Smith', 'ou=Sales'),
            'dc=example',
            'dc=net'
        );
        $expected_onlyvalues = array(
            array( 'J. Smith', 'Sales'),
            'example',
            'net'
        );
        $expected_reverse = array_reverse($expected_casefold_upper);


        $dn_exploded_cnone = Horde_Ldap_Util::explodeDN($dn, array('casefold' => 'none'));
        $this->assertEquals($expected_casefold_none, $dn_exploded_cnone, 'Option casefold none failed');

        $dn_exploded_cupper = Horde_Ldap_Util::explodeDN($dn, array('casefold' => 'upper'));
        $this->assertEquals($expected_casefold_upper, $dn_exploded_cupper, 'Option casefold upper failed');

        $dn_exploded_clower = Horde_Ldap_Util::explodeDN($dn, array('casefold' => 'lower'));
        $this->assertEquals($expected_casefold_lower, $dn_exploded_clower, 'Option casefold lower failed');

        $dn_exploded_onlyval = Horde_Ldap_Util::explodeDN($dn, array('onlyvalues' => true));
        $this->assertEquals($expected_onlyvalues, $dn_exploded_onlyval, 'Option onlyval failed');

        $dn_exploded_reverse = Horde_Ldap_Util::explodeDN($dn, array('reverse' => true));
        $this->assertEquals($expected_reverse, $dn_exploded_reverse, 'Option reverse failed');
    }

    /**
     * Tests if canonicalDN() works.
     *
     * Note: This tests depend on the default options of canonicalDN().
     */
    public function testCanonical_dn()
    {
        // Test empty dn (is valid according to RFC).
        $this->assertEquals('', Horde_Ldap_Util::canonicalDN(''));

        // Default options with common DN.
        $testdn   = 'cn=beni,DC=php,c=net';
        $expected = 'CN=beni,DC=php,C=net';
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($testdn));

        // Casefold tests with common DN.
        $expected_up = 'CN=beni,DC=php,C=net';
        $expected_lo = 'cn=beni,dc=php,c=net';
        $expected_no = 'cn=beni,DC=php,c=net';
        $this->assertEquals($expected_up, Horde_Ldap_Util::canonicalDN($testdn, array('casefold' => 'upper')));
        $this->assertEquals($expected_lo, Horde_Ldap_Util::canonicalDN($testdn, array('casefold' => 'lower')));
        $this->assertEquals($expected_no, Horde_Ldap_Util::canonicalDN($testdn, array('casefold' => 'none')));

        // Reverse.
        $expected_rev = 'C=net,DC=php,CN=beni';
        $this->assertEquals($expected_rev, Horde_Ldap_Util::canonicalDN($testdn, array('reverse' => true)), 'Option reverse failed');

        // DN as arrays.
        $dn_index = array('cn=beni', 'dc=php', 'c=net');
        $dn_assoc = array('cn' => 'beni', 'dc' => 'php', 'c' => 'net');
        $expected = 'CN=beni,DC=php,C=net';
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($dn_index));
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($dn_assoc));

        // DN with multiple RDN value.
        $testdn       = 'ou=dev+cn=beni,DC=php,c=net';
        $testdn_index = array(array('ou=dev', 'cn=beni'), 'DC=php', 'c=net');
        $testdn_assoc = array(array('ou' => 'dev', 'cn' => 'beni'), 'DC' => 'php', 'c' => 'net');
        $expected     = 'CN=beni+OU=dev,DC=php,C=net';
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($testdn));
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($testdn_assoc));
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($expected));

        // Test DN with OID.
        $testdn = 'OID.2.5.4.3=beni,dc=php,c=net';
        $expected = '2.5.4.3=beni,DC=php,C=net';
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($testdn));

        // Test with leading and ending spaces.
        $testdn   = 'cn=  beni  ,DC=php,c=net';
        $expected = 'CN=\20\20beni\20\20,DC=php,C=net';
        $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($testdn));

        // Test with to-be escaped characters in attribute value.
        $specialchars = array(
            ',' => '\,',
            '+' => '\+',
            '"' => '\"',
            '\\' => '\\\\',
            '<' => '\<',
            '>' => '\>',
            ';' => '\;',
            '#' => '\#',
            '=' => '\=',
            chr(18) => '\12',
            '/' => '\/'
        );
        foreach ($specialchars as $char => $escape) {
            $test_string = 'CN=be' . $char . 'ni,DC=ph' . $char . 'p,C=net';
            $test_index  = array('CN=be' . $char . 'ni', 'DC=ph' . $char . 'p', 'C=net');
            $test_assoc  = array('CN' => 'be' . $char . 'ni', 'DC' => 'ph' . $char . 'p', 'C' => 'net');
            $expected    = 'CN=be' . $escape . 'ni,DC=ph' . $escape . 'p,C=net';

            $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($test_string), 'String escaping test (' . $char . ') failed');
            $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($test_index),  'Indexed array escaping test (' . $char . ') failed');
            $this->assertEquals($expected, Horde_Ldap_Util::canonicalDN($test_assoc),  'Associative array encoding test (' . $char . ') failed');
        }
    }
}
