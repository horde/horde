<?php
/**
 * Load an LDAP Schema and provide information
 *
 * This class takes a Subschema entry, parses this information
 * and makes it available in an array. Most of the code has been
 * inspired by perl-ldap( http://perl-ldap.sourceforge.net).
 * You will find portions of their implementation in here.
 *
 * @category  Horde
 * @package   Ldap
 * @author    Jan Wagner <wagner@netsols.de>
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009 Jan Wagner, Benedikt Hallinger
 * @copyright 2010 The Horde Project
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL
 */
class Horde_Ldap_Schema
{
    /**
     * Syntax definitions.
     *
     * Please don't forget to add binary attributes to isBinary() below to
     * support proper value fetching from Horde_Ldap_Entry.
     */
    const SYNTAX_BOOLEAN =            '1.3.6.1.4.1.1466.115.121.1.7';
    const SYNTAX_DIRECTORY_STRING =   '1.3.6.1.4.1.1466.115.121.1.15';
    const SYNTAX_DISTINGUISHED_NAME = '1.3.6.1.4.1.1466.115.121.1.12';
    const SYNTAX_INTEGER =            '1.3.6.1.4.1.1466.115.121.1.27';
    const SYNTAX_JPEG =               '1.3.6.1.4.1.1466.115.121.1.28';
    const SYNTAX_NUMERIC_STRING =     '1.3.6.1.4.1.1466.115.121.1.36';
    const SYNTAX_OID =                '1.3.6.1.4.1.1466.115.121.1.38';
    const SYNTAX_OCTET_STRING =       '1.3.6.1.4.1.1466.115.121.1.40';

    /**
     * Map of entry types to LDAP attributes of subschema entry.
     *
     * @var array
     */
    public $types = array(
        'attribute'        => 'attributeTypes',
        'ditcontentrule'   => 'dITContentRules',
        'ditstructurerule' => 'dITStructureRules',
        'matchingrule'     => 'matchingRules',
        'matchingruleuse'  => 'matchingRuleUse',
        'nameform'         => 'nameForms',
        'objectclass'      => 'objectClasses',
        'syntax'           => 'ldapSyntaxes' );

    /**
     * Array of entries belonging to this type
     *
     * @var array
     */
    protected $_attributeTypes    = array();
    protected $_matchingRules     = array();
    protected $_matchingRuleUse   = array();
    protected $_ldapSyntaxes      = array();
    protected $_objectClasses     = array();
    protected $_dITContentRules   = array();
    protected $_dITStructureRules = array();
    protected $_nameForms         = array();


    /**
     * Hash of all fetched OIDs.
     *
     * @var array
     */
    protected $_oids = array();

    /**
     * Whether the schema is initialized.
     *
     * @see parse(), get()
     * @var boolean
     */
    protected $_initialized = false;

    /**
     * Constructor.
     *
     * Fetches the Schema from an LDAP connection.
     *
     * @param Horde_Ldap $ldap LDAP connection.
     * @param string     $dn   Subschema entry DN.
     *
     * @throws Horde_Ldap_Exception
     */
    public function __construct(Horde_Ldap $ldap, $dn = null)
    {
        if (is_null($dn)) {
            // Get the subschema entry via rootDSE.
            $dse = $ldap->rootDSE(array('subschemaSubentry'));
            $base = $dse->getValue('subschemaSubentry', 'single');
            $dn = $base;
        }

        // Support for buggy LDAP servers (e.g. Siemens DirX 6.x) that
        // incorrectly call this entry subSchemaSubentry instead of
        // subschemaSubentry. Note the correct case/spelling as per RFC 2251.
        if (is_null($dn)) {
            // Get the subschema entry via rootDSE.
            $dse = $ldap->rootDSE(array('subSchemaSubentry'));
            $base = $dse->getValue('subSchemaSubentry', 'single');
            $dn = $base;
        }

        // Final fallback in case there is no subschemaSubentry attribute in
        // the root DSE (this is a bug for an LDAPv3 server so report this to
        // your LDAP vendor if you get this far).
        if (is_null($dn)) {
            $dn = 'cn=Subschema';
        }

        // Fetch the subschema entry.
        $result = $ldap->search($dn, '(objectClass=*)',
                                array('attributes' => array_values($this->types),
                                      'scope' => 'base'));
        $entry = $result->shiftEntry();
        if (!($entry instanceof Horde_Ldap_Entry)) {
            throw new Horde_Ldap_Exception('Could not fetch Subschema entry');
        }

        $this->parse($entry);
    }

    /**
     * Returns a hash of entries for the given type.
     *
     * Types may be: objectclasses, attributes, ditcontentrules,
     * ditstructurerules, matchingrules, matchingruleuses, nameforms, syntaxes.
     *
     * @param string $type Type to fetch.
     *
     * @return array
     * @throws Horde_Ldap_Exception
     */
    public function getAll($type)
    {
        $map = array('objectclasses'     => $this->_objectClasses,
                     'attributes'        => $this->_attributeTypes,
                     'ditcontentrules'   => $this->_dITContentRules,
                     'ditstructurerules' => $this->_dITStructureRules,
                     'matchingrules'     => $this->_matchingRules,
                     'matchingruleuses'  => $this->_matchingRuleUse,
                     'nameforms'         => $this->_nameForms,
                     'syntaxes'          => $this->_ldapSyntaxes);

        $key = Horde_String::lower($type);
        if (!isset($map[$key])) {
            throw new Horde_Ldap_Exception("Unknown type $type");
        }

        return $map[$key];
    }

    /**
     * Returns a specific entry.
     *
     * @param string $type Type of name.
     * @param string $name Name or OID to fetch.
     *
     * @return mixed
     * @throws Horde_Ldap_Exception
     */
    public function get($type, $name)
    {
        if (!$this->_initialized) {
            return null;
        }

        $type = Horde_String::lower($type);
        if (!isset($this->types[$type])) {
            throw new Horde_Ldap_Exception("No such type $type");
        }

        $name     = Horde_String::lower($name);
        $type_var = $this->{'_' . $this->types[$type]};

        if (isset($type_var[$name])) {
            return $type_var[$name];
        }
        if (isset($this->_oids[$name]) &&
            $this->_oids[$name]['type'] == $type) {
            return $this->_oids[$name];
        }
        throw new Horde_Ldap_Exception("Could not find $type $name");
    }


    /**
     * Fetches attributes that MAY be present in the given objectclass.
     *
     * @param string $oc Name or OID of objectclass.
     *
     * @return array Array with attributes.
     * @throws Horde_Ldap_Exception
     */
    public function may($oc)
    {
        return $this->_getAttr($oc, 'may');
    }

    /**
     * Fetches attributes that MUST be present in the given objectclass.
     *
     * @param string $oc Name or OID of objectclass.
     *
     * @return array Array with attributes.
     * @throws Horde_Ldap_Exception
     */
    public function must($oc)
    {
        return $this->_getAttr($oc, 'must');
    }

    /**
     * Fetches the given attribute from the given objectclass.
     *
     * @param string $oc   Name or OID of objectclass.
     * @param string $attr Name of attribute to fetch.
     *
     * @return array The attribute.
     * @throws Horde_Ldap_Exception
     */
    protected function _getAttr($oc, $attr)
    {
        $oc = Horde_String::lower($oc);
        if (isset($this->_objectClasses[$oc]) &&
            isset($this->_objectClasses[$oc][$attr])) {
            return $this->_objectClasses[$oc][$attr];
        }
        if (isset($this->_oids[$oc]) &&
            $this->_oids[$oc]['type'] == 'objectclass' &&
            isset($this->_oids[$oc][$attr])) {
            return $this->_oids[$oc][$attr];
        }
        throw new Horde_Ldap_Exception("Could not find $attr attributes for $oc ");
    }

    /**
     * Returns the name(s) of the immediate superclass(es).
     *
     * @param string $oc Name or OID of objectclass.
     *
     * @return array
     * @throws Horde_Ldap_Exception
     */
    public function superclass($oc)
    {
        $o = $this->get('objectclass', $oc);
        return isset($o['sup']) ? $o['sup'] : array();
    }

    /**
     * Parses the schema of the given subschema entry.
     *
     * @param Horde_Ldap_Entry $entry Subschema entry.
     */
    public function parse($entry)
    {
        foreach ($this->types as $type => $attr) {
            // Initialize map type to entry.
            $type_var          = '_' . $attr;
            $this->{$type_var} = array();

            if (!$entry->exists($attr)) {
                continue;
            }

            // Get values for this type.
            $values = $entry->getValue($attr);
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                // Get the schema entry.
                $schema_entry = $this->_parse_entry($value);
                // Set the type.
                $schema_entry['type'] = $type;
                // Save a ref in $_oids.
                $this->_oids[$schema_entry['oid']] = $schema_entry;
                // Save refs for all names in type map.
                $names = $schema_entry['aliases'];
                array_push($names, $schema_entry['name']);
                foreach ($names as $name) {
                    $this->{$type_var}[Horde_String::lower($name)] = $schema_entry;
                }
            }
        }
        $this->_initialized = true;
    }

    /**
     * Parses an attribute value into a schema entry.
     *
     * @param string $value Attribute value.
     *
     * @return array Schema entry array.
     */
    protected function _parse_entry($value)
    {
        // Tokens that have no value associated.
        $noValue = array('single-value',
                         'obsolete',
                         'collective',
                         'no-user-modification',
                         'abstract',
                         'structural',
                         'auxiliary');

        // Tokens that can have multiple values.
        $multiValue = array('must', 'may', 'sup');

        // Get an array of tokens.
        $tokens = $this->_tokenize($value);

        // Remove surrounding brackets.
        if ($tokens[0] == '(') {
            array_shift($tokens);
        }
        if ($tokens[count($tokens) - 1] == ')') {
            array_pop($tokens);
        }

        // First token is the oid.
        $schema_entry = array('aliases' => array(),
                              'oid' => array_shift($tokens));

        // Cycle over the tokens until none are left.
        while (count($tokens) > 0) {
            $token = Horde_String::lower(array_shift($tokens));
            if (in_array($token, $noValue)) {
                // Single value token.
                $schema_entry[$token] = 1;
            } else {
                // Follow a string or a list if it is multivalued.
                if (($schema_entry[$token] = array_shift($tokens)) == '(') {
                    // Create the list of values and cycles through the tokens
                    // until the end of the list is reached ')'.
                    $schema_entry[$token] = array();
                    while ($tmp = array_shift($tokens)) {
                        if ($tmp == ')') {
                            break;
                        }
                        if ($tmp != '$') {
                            array_push($schema_entry[$token], $tmp);
                        }
                    }
                }
                // Create an array if the value should be multivalued but was
                // not.
                if (in_array($token, $multiValue) &&
                    !is_array($schema_entry[$token])) {
                    $schema_entry[$token] = array($schema_entry[$token]);
                }
            }
        }

        // Get max length from syntax.
        if (isset($schema_entry['syntax'])) {
            if (preg_match('/{(\d+)}/', $schema_entry['syntax'], $matches)) {
                $schema_entry['max_length'] = $matches[1];
            }
        }

        // Force a name.
        if (empty($schema_entry['name'])) {
            $schema_entry['name'] = $schema_entry['oid'];
        }

        // Make one name the default and put the other ones into aliases.
        if (is_array($schema_entry['name'])) {
            $aliases                 = $schema_entry['name'];
            $schema_entry['name']    = array_shift($aliases);
            $schema_entry['aliases'] = $aliases;
        }

        return $schema_entry;
    }

    /**
     * Tokenizes the given value into an array of tokens.
     *
     * @param string $value String to parse.
     *
     * @return array Array of tokens.
     */
    protected function _tokenize($value)
    {
        /* Match one big pattern where only one of the three subpatterns
         * matches.  We are interested in the subpatterns that matched. If it
         * matched its value will be non-empty and so it is a token. Tokens may
         * be round brackets, a string, or a string enclosed by ''. */
        preg_match_all("/\s* (?:([()]) | ([^'\s()]+) | '((?:[^']+|'[^\s)])*)') \s*/x", $value, $matches);

        $tokens  = array();
        // Number of tokens (full pattern match).
        for ($i = 0; $i < count($matches[0]); $i++) {
            // Each subpattern.
            for ($j = 1; $j < 4; $j++) {
                // Pattern match in this subpattern.
                if (null != trim($matches[$j][$i])) {
                    // This is the token.
                    $tokens[$i] = trim($matches[$j][$i]);
                }
            }
        }

        return $tokens;
    }

    /**
     * Returns wether a attribute syntax is binary or not.
     *
     * This method is used by Horde_Ldap_Entry to decide which PHP function
     * needs to be used to fetch the value in the proper format (e.g. binary or
     * string).
     *
     * @param string $attribute The name of the attribute (eg.: 'sn').
     *
     * @return boolean  True if the attribute is a binary type.
     */
    public function isBinary($attribute)
    {
        // All syntax that should be treaten as containing binary values.
        $syntax_binary = array(self::SYNTAX_OCTET_STRING, self::SYNTAX_JPEG);

        // Check Syntax.
        try {
            $attr_s = $this->get('attribute', $attribute);
        } catch (Horde_Ldap_Exception $e) {
            // Attribute not found in schema, consider attr not binary.
            return false;
        }

        if (isset($attr_s['syntax']) &&
            in_array($attr_s['syntax'], $syntax_binary)) {
            // Syntax is defined as binary in schema
            return true;
        }

        // Syntax not defined as binary, or not found if attribute is a
        // subtype, check superior attribute syntaxes.
        if (isset($attr_s['sup'])) {
            foreach ($attr_s['sup'] as $superattr) {
                if ($this->isBinary($superattr)) {
                    // Stop checking parents since we are binary.
                    return true;
                }
            }
        }

        return false;
    }
}
