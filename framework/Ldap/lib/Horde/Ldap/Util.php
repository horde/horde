<?php
/**
 * Utility Class for Horde_Ldap
 *
 * This class servers some functionality to the other classes of Horde_Ldap but
 * most of the methods can be used separately as well.
 *
 * @category  Horde
 * @package   Ldap
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2010-2011 The Horde Project
 * @copyright 2009 Benedikt Hallinger
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_Util
{
    /**
     * Explodes the given DN into its elements
     *
     * {@link http://www.ietf.org/rfc/rfc2253.txt RFC 2253} says, a
     * Distinguished Name is a sequence of Relative Distinguished Names (RDNs),
     * which themselves are sets of Attributes. For each RDN a array is
     * constructed where the RDN part is stored.
     *
     * For example, the DN 'OU=Sales+CN=J. Smith,DC=example,DC=net' is exploded
     * to:
     * <code>
     * array(array('OU=Sales', 'CN=J. Smith'),
     *       'DC=example',
     *       'DC=net')
     * </code>
     *
     * [NOT IMPLEMENTED] DNs might also contain values, which are the bytes of
     * the BER encoding of the X.500 AttributeValue rather than some LDAP
     * string syntax. These values are hex-encoded and prefixed with a #. To
     * distinguish such BER values, explodeDN uses references to the
     * actual values, e.g. '1.3.6.1.4.1.1466.0=#04024869,DC=example,DC=com' is
     * exploded to:
     * <code>
     * array(array('1.3.6.1.4.1.1466.0' => "\004\002Hi"),
     *       array('DC' => 'example',
     *       array('DC' => 'com'))
     * <code>
     * See {@link http://www.vijaymukhi.com/vmis/berldap.htm} for more
     * information on BER.
     *
     * It also performs the following operations on the given DN:
     * - Unescape "\" followed by ",", "+", """, "\", "<", ">", ";", "#", "=",
     *   " ", or a hexpair and strings beginning with "#".
     * - Removes the leading 'OID.' characters if the type is an OID instead of
     *   a name.
     * - If an RDN contains multiple parts, the parts are re-ordered so that
     *   the attribute type names are in alphabetical order.
     *
     * $options is a list of name/value pairs, valid options are:
     * - casefold:   Controls case folding of attribute types names.
     *               Attribute values are not affected by this option.
     *               The default is to uppercase. Valid values are:
     *               - lower: Lowercase attribute types names.
     *               - upper: Uppercase attribute type names. This is the
     *                        default.
     *               - none:  Do not change attribute type names.
     * - reverse:    If true, the RDN sequence is reversed.
     * - onlyvalues: If true, then only attributes values are returned ('foo'
     *               instead of 'cn=foo')
     *
     * @todo implement BER
     * @todo replace preg_replace() callbacks.
     *
     * @param string $dn      The DN that should be exploded.
     * @param array  $options Options to use.
     *
     * @return array   Parts of the exploded DN.
     */
    public static function explodeDN($dn, array $options = array())
    {
        if (!isset($options['onlyvalues'])) {
            $options['onlyvalues'] = false;
        }
        if (!isset($options['reverse'])) {
            $options['reverse'] = false;
        }
        if (!isset($options['casefold'])) {
            $options['casefold'] = 'upper';
        }

        // Escaping of DN and stripping of "OID.".
        $dn = self::canonicalDN($dn, array('casefold' => $options['casefold']));

        // Splitting the DN.
        $dn_array = preg_split('/(?<=[^\\\\]),/', $dn);

        // Clear wrong splitting (possibly we have split too much).
        // Not clear, if this is neccessary here:
        //$dn_array = self::_correctDNSplitting($dn_array, ',');

        // Construct subarrays for multivalued RDNs and unescape DN value, also
        // convert to output format and apply casefolding.
        foreach ($dn_array as $key => $value) {
            $value_u = self::unescapeDNValue($value);
            $rdns    = self::splitRDNMultivalue($value_u[0]);
            // TODO: nuke code duplication
            if (count($rdns) > 1) {
                // Multivalued RDN!
                foreach ($rdns as $subrdn_k => $subrdn_v) {
                    // Casefolding.
                    if ($options['casefold'] == 'upper') {
                        $subrdn_v = preg_replace('/^(\w+=)/e', "Horde_String::upper('\\1')", $subrdn_v);
                    }
                    if ($options['casefold'] == 'lower') {
                        $subrdn_v = preg_replace('/^(\w+=)/e', "Horde_String::lower('\\1')", $subrdn_v);
                    }

                    if ($options['onlyvalues']) {
                        preg_match('/(.+?)(?<!\\\\)=(.+)/', $subrdn_v, $matches);
                        $rdn_val         = $matches[2];
                        $unescaped       = self::unescapeDNValue($rdn_val);
                        $rdns[$subrdn_k] = $unescaped[0];
                    } else {
                        $unescaped = self::unescapeDNValue($subrdn_v);
                        $rdns[$subrdn_k] = $unescaped[0];
                    }
                }

                $dn_array[$key] = $rdns;
            } else {
                // Singlevalued RDN.
                // Casefolding.
                if ($options['casefold'] == 'upper') {
                    $value = preg_replace('/^(\w+=)/e', "Horde_String::upper('\\1')", $value);
                }
                if ($options['casefold'] == 'lower') {
                    $value = preg_replace('/^(\w+=)/e', "Horde_String::lower('\\1')", $value);
                }

                if ($options['onlyvalues']) {
                    preg_match('/(.+?)(?<!\\\\)=(.+)/', $value, $matches);
                    $dn_val         = $matches[2];
                    $unescaped      = self::unescapeDNValue($dn_val);
                    $dn_array[$key] = $unescaped[0];
                } else {
                    $unescaped = self::unescapeDNValue($value);
                    $dn_array[$key] = $unescaped[0];
                }
            }
        }

        if ($options['reverse']) {
            return array_reverse($dn_array);
        }

        return $dn_array;
    }

    /**
     * Escapes DN values according to RFC 2253.
     *
     * Escapes the given VALUES according to RFC 2253 so that they can be
     * safely used in LDAP DNs.  The characters ",", "+", """, "\", "<", ">",
     * ";", "#", "=" with a special meaning in RFC 2252 are preceeded by ba
     * backslash. Control characters with an ASCII code < 32 are represented as
     * \hexpair.  Finally all leading and trailing spaces are converted to
     * sequences of \20.
     *
     * @param string|array $values  DN values that should be escaped.
     *
     * @return array  The escaped values.
     */
    public static function escapeDNValue($values)
    {
        // Parameter validation.
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $key => $val) {
            // Escaping of filter meta characters.
            $val = addcslashes($val, '\\,+"<>;#=');

            // ASCII < 32 escaping.
            $val = self::asc2hex32($val);

            // Convert all leading and trailing spaces to sequences of \20.
            if (preg_match('/^(\s*)(.+?)(\s*)$/', $val, $matches)) {
                $val = str_repeat('\20', strlen($matches[1])) . $matches[2] . str_repeat('\20', strlen($matches[3]));
            }

            if (null === $val) {
                // Apply escaped "null" if string is empty.
                $val = '\0';
            }

            $values[$key] = $val;
        }

        return $values;
    }

    /**
     * Unescapes DN values according to RFC 2253.
     *
     * Reverts the conversion done by escapeDNValue().
     *
     * Any escape sequence starting with a baskslash - hexpair or special
     * character - will be transformed back to the corresponding character.
     *
     * @param array $values  DN values.
     *
     * @return array  Unescaped DN values.
     */
    public static function unescapeDNValue($values)
    {
        // Parameter validation.
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $key => $val) {
            // Strip slashes from special chars.
            $val = str_replace(
                array('\\\\', '\,', '\+', '\"', '\<', '\>', '\;', '\#', '\='),
                array('\\', ',', '+', '"', '<', '>', ';', '#', '='),
                $val);

            // Translate hex code into ascii.
            $values[$key] = self::hex2asc($val);
        }

        return $values;
    }

    /**
     * Converts a DN into a canonical form.
     *
     * DN can either be a string or an array as returned by explodeDN(),
     * which is useful when constructing a DN.  The DN array may have be
     * indexed (each array value is a OCL=VALUE pair) or associative (array key
     * is OCL and value is VALUE).
     *
     * It performs the following operations on the given DN:
     * - Removes the leading 'OID.' characters if the type is an OID instead of
     *   a name.
     * - Escapes all RFC 2253 special characters (",", "+", """, "\", "<", ">",
     *   ";", "#", "="), slashes ("/"), and any other character where the ASCII
     *   code is < 32 as \hexpair.
     * - Converts all leading and trailing spaces in values to be \20.
     * - If an RDN contains multiple parts, the parts are re-ordered so that
     *   the attribute type names are in alphabetical order.
     *
     * $options is a list of name/value pairs, valid options are:
     *
     * - casefold:  Controls case folding of attribute type names. Attribute
     *              values are not affected by this option. The default is to
     *              uppercase. Valid values are:
     *              - lower: Lowercase attribute type names.
     *              - upper: Uppercase attribute type names.
     *              - none:  Do not change attribute type names.
     * - reverse:   If true, the RDN sequence is reversed.
     * - separator: Separator to use between RDNs. Defaults to comma (',').
     *
     * The empty string "" is a valid DN, so be sure not to do a "$can_dn ==
     * false" test, because an empty string evaluates to false. Use the "==="
     * operator instead.
     *
     * @param array|string $dn      The DN.
     * @param array        $options Options to use.
     *
     * @return boolean|string The canonical DN or false if the DN is not valid.
     */
    public static function canonicalDN($dn, $options = array())
    {
        if ($dn === '') {
            // Empty DN is valid.
            return $dn;
        }

        // Options check.
        $options['reverse'] = !empty($options['reverse']);
        if (!isset($options['casefold'])) {
            $options['casefold'] = 'upper';
        }
        if (!isset($options['separator'])) {
            $options['separator'] = ',';
        }

        if (!is_array($dn)) {
            // It is not clear to me if the perl implementation splits by the
            // user defined separator or if it just uses this separator to
            // construct the new DN.
            $dn = preg_split('/(?<=[^\\\\])' . $options['separator'] . '/', $dn);

            // Clear wrong splitting (possibly we have split too much).
            $dn = self::_correctDNSplitting($dn, $options['separator']);
        } else {
            // Is array, check if the array is indexed or associative.
            $assoc = false;
            foreach ($dn as $dn_key => $dn_part) {
                if (!is_int($dn_key)) {
                    $assoc = true;
                    break;
                }
            }

            // Convert to indexed, if associative array detected.
            if ($assoc) {
                $newdn = array();
                foreach ($dn as $dn_key => $dn_part) {
                    if (is_array($dn_part)) {
                        // We assume here that the RDN parts are also
                        // associative.
                        ksort($dn_part, SORT_STRING);
                        // Copy array as-is, so we can resolve it later.
                        $newdn[] = $dn_part;
                    } else {
                        $newdn[] = $dn_key.'='.$dn_part;
                    }
                }
                $dn =& $newdn;
            }
        }

        // Escaping and casefolding.
        foreach ($dn as $pos => $dnval) {
            if (is_array($dnval)) {
                // Subarray detected, this means most probably that we had a
                // multivalued DN part, which must be resolved.
                $dnval_new = '';
                foreach ($dnval as $subkey => $subval) {
                    // Build RDN part.
                    if (!is_int($subkey)) {
                        $subval = $subkey . '=' . $subval;
                    }
                    $subval_processed = self::canonicalDN($subval);
                    if (false === $subval_processed) {
                        return false;
                    }
                    $dnval_new .= $subval_processed . '+';
                }
                // Store RDN part, strip last plus.
                $dn[$pos] = substr($dnval_new, 0, -1);
            } else {
                // Try to split multivalued RDNs into array.
                $rdns = self::splitRDNMultivalue($dnval);
                if (count($rdns) > 1) {
                    // Multivalued RDN was detected. The RDN value is expected
                    // to be correctly split by splitRDNMultivalue(). It's time
                    // to sort the RDN and build the DN.
                    $rdn_string = '';
                    // Sort RDN keys alphabetically.
                    sort($rdns, SORT_STRING);
                    foreach ($rdns as $rdn) {
                        $subval_processed = self::canonicalDN($rdn);
                        if (false === $subval_processed) {
                            return false;
                        }
                        $rdn_string .= $subval_processed . '+';
                    }

                    // Store RDN part, strip last plus.
                    $dn[$pos] = substr($rdn_string, 0, -1);
                } else {
                    // No multivalued RDN. Split at first unescaped "=".
                    $dn_comp = preg_split('/(?<=[^\\\\])=/', $rdns[0], 2);
                    // Trim left whitespaces because of "cn=foo, l=bar" syntax
                    // (whitespace after comma).
                    $ocl = ltrim($dn_comp[0]);
                    $val = $dn_comp[1];

                    // Strip 'OID.', otherwise apply casefolding and escaping.
                    if (substr(Horde_String::lower($ocl), 0, 4) == 'oid.') {
                        $ocl = substr($ocl, 4);
                    } else {
                        if ($options['casefold'] == 'upper') {
                            $ocl = Horde_String::upper($ocl);
                        }
                        if ($options['casefold'] == 'lower') {
                            $ocl = Horde_String::lower($ocl);
                        }
                        $ocl = self::escapeDNValue(array($ocl));
                        $ocl = $ocl[0];
                    }

                    // Escaping of DN value.
                    $val = self::escapeDNValue(array($val));
                    $val = str_replace('/', '\/', $val[0]);

                    $dn[$pos] = $ocl . '=' . $val;
                }
            }
        }

        if ($options['reverse']) {
            $dn = array_reverse($dn);
        }

        return implode($options['separator'], $dn);
    }

    /**
     * Escapes the given values according to RFC 2254 so that they can be
     * safely used in LDAP filters.
     *
     * Any control characters with an ACII code < 32 as well as the characters
     * with special meaning in LDAP filters "*", "(", ")", and "\" (the
     * backslash) are converted into the representation of a backslash followed
     * by two hex digits representing the hexadecimal value of the character.
     *
     * @param array $values Values to escape.
     *
     * @return array Escaped values.
     */
    public static function escapeFilterValue($values)
    {
        // Parameter validation.
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $key => $val) {
            // Escaping of filter meta characters.
            $val = str_replace(array('\\', '*', '(', ')'),
                               array('\5c', '\2a', '\28', '\29'),
                               $val);

            // ASCII < 32 escaping.
            $val = self::asc2hex32($val);

            if (null === $val) {
                // Apply escaped "null" if string is empty.
                $val = '\0';
            }

            $values[$key] = $val;
        }

        return $values;
    }

    /**
     * Unescapes the given values according to RFC 2254.
     *
     * Reverses the conversion done by {@link escapeFilterValue()}.
     *
     * Converts any sequences of a backslash followed by two hex digits into
     * the corresponding character.
     *
     * @param array $values Values to unescape.
     *
     * @return array Unescaped values.
     */
    public static function unescapeFilterValue($values = array())
    {
        // Parameter validation.
        if (!is_array($values)) {
            $values = array($values);
        }

        foreach ($values as $key => $value) {
            // Translate hex code into ascii.
            $values[$key] = self::hex2asc($value);
        }

        return $values;
    }

    /**
     * Converts all ASCII chars < 32 to "\HEX".
     *
     * @param string $string String to convert.
     *
     * @return string Hexadecimal representation of $string.
     */
    public static function asc2hex32($string)
    {
        for ($i = 0, $len = strlen($string); $i < $len; $i++) {
            $char = substr($string, $i, 1);
            if (ord($char) < 32) {
                $hex = dechex(ord($char));
                if (strlen($hex) == 1) {
                    $hex = '0' . $hex;
                }
                $string = str_replace($char, '\\' . $hex, $string);
            }
        }
        return $string;
    }

    /**
     * Converts all hexadecimal expressions ("\HEX") to their original ASCII
     * characters.
     *
     * @author beni@php.net, heavily based on work from DavidSmith@byu.net
     *
     * @param string $string String to convert.
     *
     * @return string ASCII representation of $string.
     */
    public static function hex2asc($string)
    {
        return preg_replace('/\\\([0-9A-Fa-f]{2})/e', "chr(hexdec('\\1'))", $string);
    }

    /**
     * Splits a multivalued RDN value into an array.
     *
     * A RDN can contain multiple values, spearated by a plus sign. This method
     * returns each separate ocl=value pair of the RDN part.
     *
     * If no multivalued RDN is detected, an array containing only the original
     * RDN part is returned.
     *
     * For example, the multivalued RDN 'OU=Sales+CN=J. Smith' is exploded to:
     * <kbd>array([0] => 'OU=Sales', [1] => 'CN=J. Smith')</kbd>
     *
     * The method tries to be smart if it encounters unescaped "+" characters,
     * but may fail, so better ensure escaped "+" in attribute names and
     * values.
     *
     * [BUG] If you have a multivalued RDN with unescaped plus characters and
     *       there is a unescaped plus sign at the end of an value followed by
     *       an attribute name containing an unescaped plus, then you will get
     *       wrong splitting:
     *         $rdn = 'OU=Sales+C+N=J. Smith';
     *       returns:
     *         array('OU=Sales+C', 'N=J. Smith');
     *       The "C+" is treaten as the value of the first pair instead of as
     *       the attribute name of the second pair. To prevent this, escape
     *       correctly.
     *
     * @param string $rdn Part of a (multivalued) escaped RDN (e.g. ou=foo or
     *                    ou=foo+cn=bar)
     *
     * @return array The components of the multivalued RDN.
     */
    public static function splitRDNMultivalue($rdn)
    {
        $rdns = preg_split('/(?<!\\\\)\+/', $rdn);
        $rdns = self::_correctDNSplitting($rdns, '+');
        return array_values($rdns);
    }

    /**
     * Splits a attribute=value syntax into an array.
     *
     * The split will occur at the first unescaped '=' character.
     *
     * @param string $attr An attribute-value string.
     *
     * @return array Indexed array: 0=attribute name, 1=attribute value.
     */
    public static function splitAttributeString($attr)
    {
        return preg_split('/(?<!\\\\)=/', $attr, 2);
    }

    /**
     * Corrects splitting of DN parts.
     *
     * @param array $dn        Raw DN array.
     * @param array $separator Separator that was used when splitting.
     *
     * @return array Corrected array.
     */
    protected static function _correctDNSplitting($dn = array(),
                                                    $separator = ',')
    {
        foreach ($dn as $key => $dn_value) {
            // Refresh value (foreach caches!)
            $dn_value = $dn[$key];
            // If $dn_value is not in attr=value format, we had an unescaped
            // separator character inside the attr name or the value. We assume
            // that it was the attribute value.

            // TODO: To solve this, we might ask the schema. The
            //       Horde_Ldap_Util class must remain independent from the
            //       other classes or connections though.
            if (!preg_match('/.+(?<!\\\\)=.+/', $dn_value)) {
                unset($dn[$key]);
                if (array_key_exists($key - 1, $dn)) {
                    // Append to previous attribute value.
                    $dn[$key - 1] = $dn[$key - 1] . $separator . $dn_value;
                } else {
                    // First element: prepend to next attribute name.
                    $dn[$key + 1] = $dn_value . $separator . $dn[$key + 1];
                }
            }
        }
        return array_values($dn);
    }
}
