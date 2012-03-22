<?php
/**
 * Turba directory driver implementation for PHP's LDAP extension.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@csh.rit.edu>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Driver_Ldap extends Turba_Driver
{
    /**
     * Handle for the current LDAP connection.
     *
     * @var resource
     */
    protected $_ds = 0;

    /**
     * Cache _getSyntax() calls.
     *
     * @var array
     */
    protected $_syntaxCache = array();

    /**
     * Constructs a new Turba LDAP driver object.
     *
     * @param string $name   The source name
     * @param array $params  Hash containing additional configuration parameters.
     *
     * @return Turba_Driver_Ldap
     */
    public function __construct($name = '', array $params = array())
    {
        if (!Horde_Util::extensionExists('ldap')) {
            throw new Turba_Exception(_("LDAP support is required but the LDAP module is not available or not loaded."));
        }

        $params = array_merge(array(
            'charset' => '',
            'deref' => LDAP_DEREF_NEVER,
            'multiple_entry_separator' => ', ',
            'port' => 389,
            'root' => '',
            'scope' => 'sub',
            'server' => 'localhost'
        ), $params);

        parent::__construct($name, $params);
    }

    /**
     * Initiate LDAP connection.
     *
     * Not done in __construct(), only when a read or write action is
     * necessary.
     */
    protected function _connect()
    {
        if ($this->_ds) {
            return;
        }

        if (!($this->_ds = @ldap_connect($this->_params['server'], $this->_params['port']))) {
            throw new Turba_Exception(_("Connection failure"));
        }

        /* Set the LDAP protocol version. */
        if (!empty($this->_params['version'])) {
            @ldap_set_option($this->_ds, LDAP_OPT_PROTOCOL_VERSION, $this->_params['version']);
        }

        /* Set the LDAP deref option for dereferencing aliases. */
        if (!empty($this->_params['deref'])) {
            @ldap_set_option($this->_ds, LDAP_OPT_DEREF, $this->_params['deref']);
        }

        /* Set the LDAP referrals. */
        if (!empty($this->_params['referrals'])) {
            @ldap_set_option($this->_ds, LDAP_OPT_REFERRALS, $this->_params['referrals']);
        }

        /* Start TLS if we're using it. */
        if (!empty($this->_params['tls']) &&
            !@ldap_start_tls($this->_ds)) {
            throw new Turba_Exception(sprintf(_("STARTTLS failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }

        /* Bind to the server. */
        if (isset($this->_params['bind_dn']) &&
            isset($this->_params['bind_password'])) {
            $error = !@ldap_bind($this->_ds, $this->_params['bind_dn'], $this->_params['bind_password']);
        } else {
            $error = !(@ldap_bind($this->_ds));
        }

        if ($error) {
            throw new Turba_Exception(sprintf(_("Bind failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }
    }

    /**
     * Extends parent function to build composed fields needed for the dn
     * based on the contents of $this->map.
     *
     * @param array $hash  Hash using Turba keys.
     *
     * @return array  Translated version of $hash.
     */
    public function toDriverKeys(array $hash)
    {
        // First check for combined fields in the dn-fields and add them.
        if (is_array($this->_params['dn'])) {
            foreach ($this->_params['dn'] as $param) {
                foreach ($this->map as $turbaname => $ldapname) {
                    if ((is_array($this->map[$turbaname])) &&
                        (isset($this->map[$turbaname]['attribute'])) &&
                        ($this->map[$turbaname]['attribute'] == $param)) {
                        $fieldarray = array();
                        foreach ($this->map[$turbaname]['fields'] as $mapfield) {
                            $fieldarray[] = isset($hash[$mapfield])
                                ? $hash[$mapfield]
                                : '';
                        }
                        $hash[$turbaname] = Turba::formatCompositeField($this->map[$turbaname]['format'], $fieldarray);
                    }
                }
            }
        }

        // Now convert the turba-fieldnames to ldap-fieldnames
        return parent::toDriverKeys($hash);
    }

    /**
     * Searches the LDAP directory with the given criteria and returns
     * a filtered list of results. If no criteria are specified, all
     * records are returned.
     *
     * @param array $criteria    Array containing the search criteria.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Fields that contain binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array())
    {
        $this->_connect();

        /* Build the LDAP filter. */
        $filter = '';
        if (count($criteria)) {
            foreach ($criteria as $key => $vals) {
                if ($key == 'OR') {
                    $filter .= '(|' . $this->_buildSearchQuery($vals) . ')';
                } elseif ($key == 'AND') {
                    $filter .= '(&' . $this->_buildSearchQuery($vals) . ')';
                }
            }
        } elseif (!empty($this->_params['objectclass'])) {
            /* Filter on objectclass. */
            $filter = Horde_Ldap_Filter::build(array('objectclass' => $this->_params['objectclass']), 'or');
        }

        /* Add source-wide filters, which are _always_ AND-ed. */
        if (!empty($this->_params['filter'])) {
            $filter = '(&' . '(' . $this->_params['filter'] . ')' . $filter . ')';
        }

        /* Four11 (at least) doesn't seem to return 'cn' if you don't
         * ask for 'sn' as well. Add 'sn' implicitly. */
        $attr = $fields;
        if (!in_array('sn', $attr)) {
            $attr[] = 'sn';
        }

        /* Add a sizelimit, if specified. Default is 0, which means no
         * limit.  Note: You cannot override a server-side limit with
         * this. */
        $sizelimit = 0;
        if (!empty($this->_params['sizelimit'])) {
            $sizelimit = $this->_params['sizelimit'];
        }

        /* Log the query at a DEBUG log level. */
        Horde::logMessage(sprintf('LDAP query by Turba_Driver_ldap::_search(): user = %s, root = %s (%s); filter = "%s"; attributes = "%s"; deref = "%s"  ; sizelimit = %d',
                                  $GLOBALS['registry']->getAuth(), $this->_params['root'], $this->_params['server'], $filter, implode(', ', $attr), $this->_params['deref'], $sizelimit), 'DEBUG');

        /* Send the query to the LDAP server and fetch the matching
         * entries. */
        $func = ($this->_params['scope'] == 'one')
            ? 'ldap_list'
            : 'ldap_search';

        if (!($res = @$func($this->_ds, $this->_params['root'], $filter, $attr, 0, $sizelimit))) {
            throw new Turba_Exception(sprintf(_("Query failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }

        return $this->_getResults($fields, $res);
    }

    /**
     * Reads the LDAP directory for a given element and returns the results.
     *
     * @param string $key        The primary key field to use.
     * @param mixed $ids         The ids of the contacts to load.
     * @param string $owner      Only return contacts owned by this user.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, array $fields,
                             array $blobFields = array())
    {
        /* Only DN. */
        if ($key != 'dn') {
            return array();
        }

        $this->_connect();

        if (empty($this->_params['objectclass'])) {
            $filter = null;
        } else {
            $filter = (string)Horde_Ldap_Filter::build(array('objectclass' => $this->_params['objectclass']), 'or');
        }

        /* Four11 (at least) doesn't seem to return 'cn' if you don't
         * ask for 'sn' as well. Add 'sn' implicitly. */
        $attr = $fields;
        if (!in_array('sn', $attr)) {
            $attr[] = 'sn';
        }

        /* Handle a request for multiple records. */
        if (is_array($ids) && !empty($ids)) {
            $results = array();
            foreach ($ids as $d) {
                $res = @ldap_read($this->_ds, Horde_String::convertCharset($d, 'UTF-8', $this->_params['charset']), $filter, $attr);
                if ($res) {
                    $results = array_merge($results, $this->_getResults($fields, $res));
                } else {
                    throw new Turba_Exception(sprintf(_("Read failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
                }
            }

            return $results;
        }

        $res = @ldap_read($this->_ds, Horde_String::convertCharset($this->_params['root'], 'UTF-8', $this->_params['charset']), $filter, $attr);
        if (!$res) {
            throw new Turba_Exception(sprintf(_("Read failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }

        return $this->_getResults($fields, $res);
    }

    /**
     * Adds the specified contact to the addressbook.
     *
     * @param array $attributes  The attribute values of the contact.
     * @param array $blob_fields TODO
     *
     * @throws Turba_Exception
     */
    protected function _add(array $attributes, array $blob_fields = array())
    {
        if (empty($attributes['dn'])) {
            throw new Turba_Exception('Tried to add an object with no dn: [' . serialize($attributes) . '].');
        }
        if (empty($this->_params['objectclass'])) {
            throw new Turba_Exception('Tried to add an object with no objectclass: [' . serialize($attributes) . '].');
        }

        $this->_connect();

        /* Take the DN out of the attributes array. */
        $dn = $attributes['dn'];
        unset($attributes['dn']);

        /* Put the objectClass into the attributes array. */
        if (!is_array($this->_params['objectclass'])) {
            $attributes['objectclass'] = $this->_params['objectclass'];
        } else {
            $i = 0;
            foreach ($this->_params['objectclass'] as $objectclass) {
                $attributes['objectclass'][$i++] = $objectclass;
            }
        }

        /* Don't add empty attributes. */
        $attributes = array_filter($attributes, array($this, '_emptyAttributeFilter'));

        /* If a required attribute doesn't exist, add a dummy
         * value. */
        if (!empty($this->_params['checkrequired'])) {
            $required = $this->_checkRequiredAttributes($this->_params['objectclass']);

            foreach ($required as $k => $v) {
                if (!isset($attributes[$v])) {
                    $attributes[$v] = $this->_params['checkrequired_string'];
                }
            }
        }

        $this->_encodeAttributes($attributes);

        if (!@ldap_add($this->_ds, Horde_String::convertCharset($dn, 'UTF-8', $this->_params['charset']), $attributes)) {
            throw new Turba_Exception('Failed to add an object: [' . ldap_errno($this->_ds) . '] "' . ldap_error($this->_ds) . '" DN: ' . $dn . ' (attributes: [' . serialize($attributes) . '])');
        }
    }

    /**
     * TODO
     *
     * @return boolean  TODO
     */
    protected function _canAdd()
    {
        return true;
    }

    /**
     * Deletes the specified entry from the LDAP directory.
     *
     * @param string $object_key
     * @param string $object_id
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        if ($object_key != 'dn') {
            throw new Turba_Exception(_("Invalid key specified."));
        }

        $this->_connect();

        if (!@ldap_delete($this->_ds, Horde_String::convertCharset($object_id, 'UTF-8', $this->_params['charset']))) {
            throw new Turba_Exception(sprintf(_("Delete failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }
    }

    /**
     * Modifies the specified entry in the LDAP directory.
     *
     * @param Turba_Object $object  The object we wish to save.
     *
     * @return string  The object id, possibly updated.
     * @throw Turba_Exception
     */
    protected function _save(Turba_Object $object)
    {
        $this->_connect();

        list($object_key, $object_id) = each($this->toDriverKeys(array('__key' => $object->getValue('__key'))));
        $attributes = $this->toDriverKeys($object->getAttributes());

        /* Get the old entry so that we can access the old
         * values. These are needed so that we can delete any
         * attributes that have been removed by using ldap_mod_del. */
        if (empty($this->_params['objectclass'])) {
            $filter = null;
        } else {
            $filter = (string)Horde_Ldap_Filter::build(array('objectclass' => $this->_params['objectclass']), 'or');
        }
        $oldres = @ldap_read($this->_ds, Horde_String::convertCharset($object_id, 'UTF-8', $this->_params['charset']), $filter, array_merge(array_keys($attributes), array('objectclass')));
        $info = ldap_get_attributes($this->_ds, ldap_first_entry($this->_ds, $oldres));

        if ($this->_params['version'] == 3 &&
            Horde_String::lower(str_replace(array(',', '"'), array('\\2C', ''), $this->_makeKey($attributes))) !=
            Horde_String::lower(str_replace(',', '\\2C', $object_id))) {
            /* Need to rename the object. */
            $newrdn = $this->_makeRDN($attributes);
            if ($newrdn == '') {
                throw new Turba_Exception(_("Missing DN in LDAP source configuration."));
            }

            if (ldap_rename($this->_ds, Horde_String::convertCharset($object_id, 'UTF-8', $this->_params['charset']),
                            Horde_String::convertCharset($newrdn, 'UTF-8', $this->_params['charset']), $this->_params['root'], true)) {
                $object_id = $newrdn . ',' . $this->_params['root'];
            } else {
                throw new Turba_Exception(sprintf(_("Failed to change name: (%s) %s; Old DN = %s, New DN = %s, Root = %s"), ldap_errno($this->_ds), ldap_error($this->_ds), $object_id, $newrdn, $this->_params['root']));
            }
        }

        /* Work only with lowercase keys. */
        $info = array_change_key_case($info, CASE_LOWER);
        $attributes = array_change_key_case($attributes, CASE_LOWER);

        foreach ($info as $key => $value) {
            $var = $info[$key];
            $oldval = null;

            /* Check to see if the old value and the new value are
             * different and that the new value is empty. If so then
             * we use ldap_mod_del to delete the attribute. */
            if (isset($attributes[$key]) &&
                ($var[0] != $attributes[$key]) &&
                $attributes[$key] == '') {

                $oldval[$key] = $var[0];
                if (!@ldap_mod_del($this->_ds, Horde_String::convertCharset($object_id, 'UTF-8', $this->_params['charset']), $oldval)) {
                    throw new Turba_Exception(sprintf(_("Modify failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
                }
                unset($attributes[$key]);
            } elseif (isset($attributes[$key]) &&
                      $var[0] == $attributes[$key]) {
                /* Drop unchanged elements from list of attributes to write. */
                unset($attributes[$key]);
            }
        }

        unset($attributes[Horde_String::lower($object_key)]);
        $this->_encodeAttributes($attributes);
        $attributes = array_filter($attributes, array($this, '_emptyAttributeFilter'));

        /* Modify objectclasses only if they really changed. */
        $oldClasses = array_map(array('Horde_String', 'lower'), $info['objectclass']);
        array_shift($oldClasses);
        $attributes['objectclass'] = array_unique(array_map('strtolower', array_merge($info['objectclass'], $this->_params['objectclass'])));
        unset($attributes['objectclass']['count']);
        $attributes['objectclass'] = array_values($attributes['objectclass']);

        /* Do not handle object classes unless they have changed. */
        if ((!array_diff($oldClasses, $attributes['objectclass']))) {
            unset($attributes['objectclass']);
        }
        if (!@ldap_modify($this->_ds, Horde_String::convertCharset($object_id, 'UTF-8', $this->_params['charset']), $attributes)) {
            throw new Turba_Exception(sprintf(_("Modify failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }

        return $object_id;
    }

    /**
     * Build a RDN based on a set of attributes and what attributes
     * make a RDN for the current source.
     *
     * @param array $attributes The attributes (in driver keys) of the
     *                          object being added.
     *
     * @return string  The RDN for the new object.
     */
    protected function _makeRDN(array $attributes)
    {
        if (!is_array($this->_params['dn'])) {
            return '';
        }

        $pairs = array();
        foreach ($this->_params['dn'] as $param) {
            if (isset($attributes[$param])) {
                $pairs[] = array($param, $attributes[$param]);
            }
        }

        return Horde_Ldap::quoteDN($pairs);
    }

    /**
     * Build a DN based on a set of attributes and what attributes
     * make a DN for the current source.
     *
     * @param array $attributes The attributes (in driver keys) of the
     *                          object being added.
     *
     * @return string  The DN for the new object.
     */
    protected function _makeKey(array $attributes)
    {
        return $this->_makeRDN($attributes) . ',' . $this->_params['root'];
    }

    /**
     * Build a piece of a search query.
     *
     * @param array  $criteria  The array of criteria.
     *
     * @return string  An LDAP query fragment.
     */
    protected function _buildSearchQuery(array $criteria)
    {
        $clause = '';

        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])) {
                $clause .= '(|' . $this->_buildSearchQuery($vals) . ')';
            } elseif (!empty($vals['AND'])) {
                $clause .= '(&' . $this->_buildSearchQuery($vals) . ')';
            } else {
                if (isset($vals['field'])) {
                    $rhs = Horde_String::convertCharset($vals['test'], 'UTF-8', $this->_params['charset']);
                    $clause .= Horde_Ldap::buildClause($vals['field'], $vals['op'], $rhs, array('begin' => !empty($vals['begin'])));
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $clause .= '(|' . $this->_buildSearchQuery($test) . ')';
                        } elseif (!empty($test['AND'])) {
                            $clause .= '(&' . $this->_buildSearchQuery($test) . ')';
                        } else {
                            $rhs = Horde_String::convertCharset($test['test'], 'UTF-8', $this->_params['charset']);
                            $clause .= Horde_Ldap::buildClause($test['field'], $test['op'], $rhs, array('begin' => !empty($vals['begin'])));
                        }
                    }
                }
            }
        }

        return $clause;
    }

    /**
     * Get some results from a result identifier and clean them up.
     *
     * @param array    $fields  List of fields to return.
     * @param resource $res     Result identifier.
     *
     * @return array  Hash containing the results.
     * @throws Turba_Exception
     */
    protected function _getResults(array $fields, $res)
    {
        $entries = @ldap_get_entries($this->_ds, $res);
        if ($entries === false) {
            throw new Turba_Exception(sprintf(_("Read failed: (%s) %s"), ldap_errno($this->_ds), ldap_error($this->_ds)));
        }

        /* Return only the requested fields (from $fields, above). */
        $results = array();
        for ($i = 0; $i < $entries['count']; ++$i) {
            $entry = $entries[$i];
            $result = array();

            foreach ($fields as $field) {
                $field_l = Horde_String::lower($field);
                if ($field == 'dn') {
                    $result[$field] = Horde_String::convertCharset($entry[$field_l], $this->_params['charset'], 'UTF-8');
                } else {
                    $result[$field] = '';
                    if (!empty($entry[$field_l])) {
                        for ($j = 0; $j < $entry[$field_l]['count']; $j++) {
                            if (!empty($result[$field])) {
                                $result[$field] .= $this->_params['multiple_entry_separator'];
                            }
                            $result[$field] .= Horde_String::convertCharset($entry[$field_l][$j], $this->_params['charset'], 'UTF-8');
                        }

                        /* If schema checking is enabled check the
                         * backend syntax. */
                        if (!empty($this->_params['checksyntax'])) {
                            $postal = $this->_isPostalAddress($field_l);
                        } else {
                            /* Otherwise rely on the attribute mapping
                             * in attributes.php. */
                            $attr = array_search($field_l, $this->map);
                            $postal = (!empty($attr) && !empty($GLOBALS['attributes'][$attr]) &&
                                       $GLOBALS['attributes'][$attr]['type'] == 'address');
                        }
                        if ($postal) {
                            $result[$field] = str_replace('$', "\r\n", $result[$field]);
                        }
                    }
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Remove empty attributes from attributes array.
     *
     * @param mixed $val  Value from attributes array.
     *
     * @return boolean  Boolean used by array_filter.
     */
    protected function _emptyAttributeFilter($var)
    {
        if (!is_array($var)) {
            return ($var != '');
        }

        if (!count($var)) {
            return false;
        }

        foreach ($var as $v) {
            if ($v == '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Format and encode attributes including postal addresses,
     * character set encoding, etc.
     *
     * @param array $attributes  The attributes array.
     */
    protected function _encodeAttributes(&$attributes)
    {
        foreach ($attributes as $key => $val) {
            /* If schema checking is enabled check the backend syntax. */
            if (!empty($this->_params['checksyntax'])) {
                $postal = $this->_isPostalAddress($key);
            } else {
                /* Otherwise rely on the attribute mapping in
                 * attributes.php. */
                $attr = array_search($key, $this->map);
                $postal = (!empty($attr) && !empty($val) && !empty($GLOBALS['attributes'][$attr]) &&
                           $GLOBALS['attributes'][$attr]['type'] == 'address');
            }
            if ($postal) {
                /* Correctly store postal addresses. */
                $val = str_replace(array("\r\n", "\r", "\n"), '$', $val);
            }

            if (!is_array($val)) {
                $attributes[$key] = Horde_String::convertCharset($val, 'UTF-8', $this->_params['charset']);
            }
        }
    }

    /**
     * Returns a list of required attributes.
     *
     * @param array $objectclasses  List of objectclasses that should be
     *                              checked for required attributes.
     *
     * @return array  List of attribute names of the specified objectclasses
     *                that have been configured as being required.
     * @throws Turba_Exception
     */
    protected function _checkRequiredAttributes(array $objectclasses)
    {
        $ldap = new Horde_Ldap($this->_convertParameters($this->_params));
        $schema = $ldap->schema();

        $retval = array();
        foreach ($objectclasses as $oc) {
            if (Horde_String::lower($oc) == 'top') {
                continue;
            }

            $required = $schema->must($oc, true);
            if (is_array($required)) {
                foreach ($required as $v) {
                    if ($this->_isString($v)) {
                        $retval[] = Horde_String::lower($v);
                    }
                }
            }
        }

        return $retval;
    }

    /**
     * Checks if an attribute refers to a string.
     *
     * @param string $attribute  An attribute name.
     *
     * @return boolean  True if the specified attribute refers to a string.
     */
    protected function _isString($attribute)
    {
        $syntax = $this->_getSyntax($attribute);

        /* Syntaxes we want to allow, i.e. no integers.
         * Syntaxes have the form:
         * 1.3.6.1.4.1.1466.115.121.1.$n{$y}
         * ... where $n is the integer used below and $y is a sizelimit. */
        $okSyntax = array(
            44 => 1, /* Printable string. */
            41 => 1, /* Postal address. */
            39 => 1, /* Other mailbox. */
            34 => 1, /* Name and optional UID. */
            26 => 1, /* IA5 string. */
            15 => 1, /* Directory string. */
        );

        return (preg_match('/^(.*)\.(\d+)\{\d+\}$/', $syntax, $matches) &&
                ($matches[1] == "1.3.6.1.4.1.1466.115.121.1") &&
                isset($okSyntax[$matches[2]]));
    }

    /**
     * Checks if an attribute refers to a Postal Address.
     *
     * @param string $attribute  An attribute name.
     *
     * @return boolean  True if the specified attribute refers to a Postal
     *                  Address.
     */
    protected function _isPostalAddress($attribute)
    {
        /* LDAP postal address syntax is
         * 1.3.6.1.4.1.1466.115.121.1.41 */
        return ($this->_getSyntax($attribute) == '1.3.6.1.4.1.1466.115.121.1.41');
    }

    /**
     * Returns the syntax of an attribute, if necessary recursively.
     *
     * @param string $att  Attribute name.
     *
     * @return string  Attribute syntax.
     * @throws Turba_Exception
     */
    protected function _getSyntax($att)
    {
        $ldap = new Horde_Ldap($this->_convertParameters($this->_params));
        $schema = $ldap->schema();

        if (!isset($this->_syntaxCache[$att])) {
            $attv = $schema->get('attribute', $att);
            $this->_syntaxCache[$att] = isset($attv['syntax'])
                ? $attv['syntax']
                : $this->_getSyntax($attv['sup'][0]);
        }

        return $this->_syntaxCache[$att];
    }

    /**
     * Converts Turba connection parameter so Horde_Ldap parameters.
     *
     * @param array $in  Turba parameters.
     *
     * @return array  Horde_Ldap parameters.
     */
    protected function _convertParameters(array $in)
    {
        $map = array(
            'server' => 'hostspec',
            'port' => 'port',
            'tls' => 'tls',
            'version' => 'version',
            'root' => 'basedn',
            'bind_dn' => 'binddn',
            'bind_password' => 'bindpw',
            // can both be specified in Turba but only one in Horde_Ldap.
            //'objectclass',
            //'filter' => 'filter',
            'scope' => 'scope',
            // charset is always utf-8
            //'charset',
            // Not yet implemented.
            //'deref',
            //'referrals',
            //'sizelimit',
            //'dn',
        );
        $out = array();
        foreach ($in as $key => $value) {
            if (isset($map[$key])) {
                $out[$map[$key]] = $value;
            }
        }
        return $out;
    }
}
