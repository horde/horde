<?php
/**
 * Horde_Ldap_Entry represents an LDAP entry.
 *
 * @package   Ldap
 * @author    Jan Wagner <wagner@netsols.de>
 * @author    Tarjej Huse <tarjei@bergfald.no>
 * @author    Benedikt Hallinger <beni@php.net>
 * @author    Ben Klang <ben@alkaloid.net>
 * @author    Jan Schneider <jan@horde.org>
 * @copyright 2009-2010 The Horde Project
 * @copyright 2003-2007 Tarjej Huse, Jan Wagner, Benedikt Hallinger
 * @license   http://www.gnu.org/licenses/lgpl-3.0.txt LGPLv3
 */
class Horde_Ldap_Entry
{
    /**
     * Entry resource identifier.
     *
     * @var resource
     */
    protected $_entry;

    /**
     * LDAP resource identifier.
     *
     * @var resource
     */
    protected $_link;

    /**
     * Horde_Ldap object.
     *
     * This object will be used for updating and schema checking.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Distinguished name of the entry.
     *
     * @var string
     */
    protected $_dn;

    /**
     * Attributes.
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * Original attributes before any modification.
     *
     * @var array
     */
    protected $_original = array();

    /**
     * Map of attribute names.
     *
     * @var array
     */
    protected $_map = array();

    /**
     * Is this a new entry?
     *
     * @var boolean
     */
    protected $_new = true;

    /**
     * New distinguished name.
     *
     * @var string
     */
    protected $_newdn;

    /**
     * Shall the entry be deleted?
     *
     * @var boolean
     */
    protected $_delete = false;

    /**
     * Map with changes to the entry.
     *
     * @var array
     */
    protected $_changes = array('add'     => array(),
                                'delete'  => array(),
                                'replace' => array());

    /**
     * Constructor.
     *
     * Sets up the distinguished name and the entries attributes.
     *
     * Use {@link Horde_Ldap_Entry::createFresh()} or {@link
     * Horde_Ldap_Entry::createConnected()} to create Horde_Ldap_Entry objects.
     *
     * @param Horde_Ldap|resource|array $ldap Horde_Ldap object, LDAP
     *                                        connection resource or
     *                                        array of attributes.
     * @param string|resource          $entry Either a DN or a LDAP entry
     *                                        resource.
     */
    protected function __construct($ldap, $entry = null)
    {
        /* Set up entry resource or DN. */
        if (is_resource($entry)) {
            $this->_entry = $entry;
        } else {
            $this->_dn = $entry;
        }

        /* Set up LDAP link. */
        if ($ldap instanceof Horde_Ldap) {
            $this->_ldap = $ldap;
            $this->_link = $ldap->getLink();
        } elseif (is_resource($ldap)) {
            $this->_link = $ldap;
        } elseif (is_array($ldap)) {
            /* Special case: here $ldap is an array of attributes, this means,
             * we have no link. This is a "virtual" entry.  We just set up the
             * attributes so one can work with the object as expected, but an
             * update() fails unless setLDAP() is called. */
            $this->_loadAttributes($ldap);
        }

        /* If this is an entry existing in the directory, then set up as old
         * and fetch attributes. */
        if (is_resource($this->_entry) && is_resource($this->_link)) {
            $this->_new = false;
            $this->_dn  = @ldap_get_dn($this->_link, $this->_entry);
            /* Fetch attributes from server. */
            $this->_loadAttributes();
        }
    }

    /**
     * Creates a fresh entry that may be added to the directory later.
     *
     * You should put a 'objectClass' attribute into the $attrs so the
     * directory server knows which object you want to create. However, you may
     * omit this in case you don't want to add this entry to a directory
     * server.
     *
     * The attributes parameter is as following:
     * <code>
     * $attrs = array('attribute1' => array('value1', 'value2'),
     *                'attribute2' => 'single value');
     * </code>
     *
     * @param string $dn    DN of the entry.
     * @param array  $attrs Attributes of the entry.
     *
     * @return Horde_Ldap_Entry
     * @throws Horde_Ldap_Exception
     */
    public static function createFresh($dn, array $attrs = array())
    {
        return new Horde_Ldap_Entry($attrs, $dn);
    }

    /**
     * Creates an entry object out of an LDAP entry resource.
     *
     * Use this method, if you want to initialize an entry object that is
     * already present in some directory and that you have read manually.
     *
     * @param Horde_Ldap $ldap Horde_Ldap object.
     * @param resource  $entry PHP LDAP entry resource.
     *
     * @return Horde_Ldap_Entry
     * @throws Horde_Ldap_Exception
     */
    public static function createConnected(Horde_Ldap $ldap, $entry)
    {
        if (!is_resource($entry)) {
            throw new Horde_Ldap_Exception('Unable to create connected entry: Parameter $entry needs to be a ldap entry resource!');
        }

        return new Horde_Ldap_Entry($ldap, $entry);
    }

    /**
     * Creates an entry object that is considered to exist already.
     *
     * Use this method, if you want to modify an already existing entry without
     * fetching it first.  In most cases however, it is better to fetch the
     * entry via Horde_Ldap::getEntry().
     *
     * You should take care if you construct entries manually with this because
     * you may get weird synchronisation problems.  The attributes and values
     * as well as the entry itself are considered existent which may produce
     * errors if you try to modify an entry which doesn't really exist or if
     * you try to overwrite some attribute with an value already present.
     *
     * The attributes parameter is as following:
     * <code>
     * $attrs = array('attribute1' => array('value1', 'value2'),
     *                'attribute2' => 'single value');
     * </code>
     *
     * @param string $dn    DN of the entry.
     * @param array  $attrs Attributes of the entry.
     *
     * @return Horde_Ldap_Entry
     * @throws Horde_Ldap_Exception
     */
    public static function createExisting($dn, array $attrs = array())
    {
        $entry = self::createFresh($dn, $attrs);
        $entry->markAsNew(false);
        return $entry;
    }

    /**
     * Returns or sets the distinguished name of the entry.
     *
     * If called without an argument the current (or the new DN if set) DN gets
     * returned.
     *
     * If you provide an DN, this entry is moved to the new location specified
     * if a DN existed.
     *
     * If the DN was not set, the DN gets initialized. Call {@link update()} to
     * actually create the new entry in the directory.
     *
     * To fetch the current active DN after setting a new DN but before an
     * update(), you can use {@link currentDN()} to retrieve the DN that is
     * currently active.
     *
     * @todo expect utf-8 data.
     * Please note that special characters (eg german umlauts) should be encoded using utf8_encode().
     * You may use {@link Horde_Ldap_Util::canonicalDN()} for properly encoding of the DN.
     *
     * @param string $dn New distinguished name.
     *
     * @return string Distinguished name.
     */
    public function dn($dn = null)
    {
        if (!is_null($dn)) {
            if (is_null($this->_dn)) {
                $this->_dn = $dn;
            } else {
                $this->_newdn = $dn;
            }
            return $dn;
        }
        return isset($this->_newdn) ? $this->_newdn : $this->currentDN();
    }

    /**
     * Sets the internal attributes array.
     *
     * This method fetches the values for the attributes from the server.  The
     * attribute syntax will be checked so binary attributes will be returned
     * as binary values.
     *
     * Attributes may be passed directly via the $attributes parameter to setup
     * this entry manually. This overrides attribute fetching from the server.
     *
     * @param array $attributes Attributes to set for this entry.
     */
    protected function _loadAttributes(array $attributes = null)
    {
        /* Fetch attributes from the server. */
        if (is_null($attributes) &&
            is_resource($this->_entry) &&
            is_resource($this->_link)) {
            /* Fetch schema. */
            if ($this->_ldap instanceof Horde_Ldap) {
                try {
                    $schema = $this->_ldap->schema();
                } catch (Horde_Ldap_Exception $e) {
                    $schema = null;
                }
            }

            /* Fetch attributes. */
            $attributes = array();
            for ($attr = @ldap_first_attribute($this->_link, $this->_entry, $ber);
                 $attr;
                 $attr = @ldap_next_attribute($this->_link, $this->_entry, $ber)) {
                /* Standard function to fetch value. */
                $func = 'ldap_get_values';

                /* Try to get binary values as binary data. */
                if ($schema instanceof Horde_Ldap_Schema &&
                    $schema->isBinary($attr)) {
                    $func = 'ldap_get_values_len';
                }

                /* Fetch attribute value (needs error checking?) . */
                $attributes[$attr] = $func($this->_link, $this->_entry, $attr);
            }
        }

        /* Set attribute data directly, if passed. */
        if (is_array($attributes) && count($attributes) > 0) {
            if (isset($attributes['count']) &&
                is_numeric($attributes['count'])) {
                unset($attributes['count']);
            }
            foreach ($attributes as $k => $v) {
                /* Attribute names should not be numeric. */
                if (is_numeric($k)) {
                    continue;
                }

                /* Map generic attribute name to real one. */
                $this->_map[Horde_String::lower($k)] = $k;

                /* Attribute values should be in an array. */
                if (false == is_array($v)) {
                    $v = array($v);
                }

                /* Remove the value count (comes from LDAP server). */
                if (isset($v['count'])) {
                    unset($v['count']);
                }
                $this->_attributes[$k] = $v;
            }
        }

        /* Save a copy for later use. */
        $this->_original = $this->_attributes;
    }

    /**
     * Returns the values of all attributes in a hash.
     *
     * The returned hash has the form
     * <code>
     * array('attributename' => 'single value',
     *       'attributename' => array('value1', value2', value3'))
     * </code>
     *
     * @return array Hash of all attributes with their values.
     * @throws Horde_Ldap_Exception
     */
    public function getValues()
    {
        $attrs = array();
        foreach (array_keys($this->_attributes) as $attr) {
            $attrs[$attr] = $this->getValue($attr);
        }
        return $attrs;
    }

    /**
     * Returns the value of a specific attribute.
     *
     * The first parameter is the name of the attribute.
     *
     * The second parameter influences the way the value is returned:
     * - 'single': only the first value is returned as string.
     * - 'all': all values including the value count are returned in an
     *          array.
     * In all other cases an attribute value with a single value is returned as
     * string, if it has multiple values it is returned as an array (without
     * value count).
     *
     * @param string $attr   Attribute name.
     * @param string $option Option.
     *
     * @return string|array Attribute value(s).
     * @throws Horde_Ldap_Exception
     */
    public function getValue($attr, $option = null)
    {
        $attr = $this->_getAttrName($attr);

        if (!array_key_exists($attr, $this->_attributes)) {
            throw new Horde_Ldap_Exception('Unknown attribute (' . $attr . ') requested');
        }

        $value = $this->_attributes[$attr];

        if ($option == 'single' || (count($value) == 1 && $option != 'all')) {
            $value = array_shift($value);
        }

        return $value;
    }

    /**
     * Returns an array of attributes names.
     *
     * @return array Array of attribute names.
     */
    public function attributes()
    {
        return array_keys($this->_attributes);
    }

    /**
     * Returns whether an attribute exists or not.
     *
     * @param string $attr Attribute name.
     *
     * @return boolean True if the attribute exists.
     */
    public function exists($attr)
    {
        $attr = $this->_getAttrName($attr);
        return array_key_exists($attr, $this->_attributes);
    }

    /**
     * Adds new attributes or a new values to existing attributes.
     *
     * The paramter has to be an array of the form:
     * <code>
     * array('attributename' => 'single value',
     *       'attributename' => array('value1', 'value2'))
     * </code>
     *
     * When the attribute already exists the values will be added, otherwise
     * the attribute will be created. These changes are local to the entry and
     * do not affect the entry on the server until update() is called.
     *
     * You can add values of attributes that you haven't originally selected,
     * but if you do so, {@link getValue()} and {@link getValues()} will only
     * return the values you added, *NOT* all values present on the server. To
     * avoid this, just refetch the entry after calling {@link update()} or
     * select the attribute.
     *
     * @param array $attr Attributes to add.
     */
    public function add(array $attr = array())
    {
        foreach ($attr as $k => $v) {
            $k = $this->_getAttrName($k);
            if (!is_array($v)) {
                /* Do not add empty values. */
                if ($v == null) {
                    continue;
                } else {
                    $v = array($v);
                }
            }

            /* Add new values to existing attribute or add new attribute. */
            if ($this->exists($k)) {
                $this->_attributes[$k] = array_unique(array_merge($this->_attributes[$k], $v));
            } else {
                $this->_map[Horde_String::lower($k)] = $k;
                $this->_attributes[$k]      = $v;
            }

            /* Save changes for update(). */
            if (empty($this->_changes['add'][$k])) {
                $this->_changes['add'][$k] = array();
            }
            $this->_changes['add'][$k] = array_unique(array_merge($this->_changes['add'][$k], $v));
        }
    }

    /**
     * Deletes an attribute, a value or the whole entry.
     *
     * The parameter can be one of the following:
     *
     * - 'attributename': the attribute as a whole will be deleted.
     * - array('attributename1', 'attributename2'): all specified attributes
     *                                              will be deleted.
     * - array('attributename' => 'value'): the specified attribute value will
     *                                      be deleted.
     * - array('attributename' => array('value1', 'value2'): The specified
     *                                                       attribute values
     *                                                       will be deleted.
     * - null: the whole entry will be deleted.
     *
     * These changes are local to the entry and do not affect the entry on the
     * server until {@link update()} is called.
     *
     * You must select the attribute (at $ldap->search() for example) to be
     * able to delete values of it, Otherwise {@link update()} will silently
     * fail and remove nothing.
     *
     * @param string|array $attr Attributes to delete.
     */
    public function delete($attr = null)
    {
        if (is_null($attr)) {
            $this->_delete = true;
            return;
        }

        if (is_string($attr)) {
            $attr = array($attr);
        }

        /* Make the assumption that attribute names cannot be numeric,
         * therefore this has to be a simple list of attribute names to
         * delete. */
        reset($attr);
        if (is_numeric(key($attr))) {
            foreach ($attr as $name) {
                if (is_array($name)) {
                    /* Mixed modes (list mode but specific values given!). */
                    $del_attr_name = array_search($name, $attr);
                    $this->delete(array($del_attr_name => $name));
                } else {
                    /* Mark for update() if this attribute was not marked
                     before. */
                    $name = $this->_getAttrName($name);
                    if ($this->exists($name)) {
                        $this->_changes['delete'][$name] = null;
                        unset($this->_attributes[$name]);
                    }
                }
            }
        } else {
            /* We have a hash with 'attributename' => 'value to delete'. */
            foreach ($attr as $name => $values) {
                if (is_int($name)) {
                    /* Mixed modes and gave us just an attribute name. */
                    $this->delete($values);
                } else {
                    /* Mark for update() if this attribute was not marked
                     * before; this time it must consider the selected values
                     * too. */
                    $name = $this->_getAttrName($name);
                    if ($this->exists($name)) {
                        if (!is_array($values)) {
                            $values = array($values);
                        }
                        /* Save values to be deleted. */
                        if (empty($this->_changes['delete'][$name])) {
                            $this->_changes['delete'][$name] = array();
                        }
                        $this->_changes['delete'][$name] =
                            array_unique(array_merge($this->_changes['delete'][$name], $values));
                        foreach ($values as $value) {
                            /* Find the key for the value that should be
                             * deleted. */
                            $key = array_search($value, $this->_attributes[$name]);
                            if (false !== $key) {
                                /* Delete the value. */
                                unset($this->_attributes[$name][$key]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Replaces attributes or their values.
     *
     * The parameter has to an array of the following form:
     * <code>
     * array('attributename' => 'single value',
     *       'attribute2name' => array('value1', 'value2'),
     *       'deleteme1' => null,
     *       'deleteme2' => '')
     * </code>
     *
     * If the attribute does not yet exist it will be added instead (see also
     * $force). If the attribue value is null, the attribute will de deleted.
     *
     * These changes are local to the entry and do not affect the entry on the
     * server until {@link update()} is called.
     *
     * In some cases you are not allowed to read the attributes value (for
     * example the ActiveDirectory attribute unicodePwd) but are allowed to
     * replace the value. In this case replace() would assume that the
     * attribute is not in the directory yet and tries to add it which will
     * result in an LDAP_TYPE_OR_VALUE_EXISTS error. To force replace mode
     * instead of add, you can set $force to true.
     *
     * @param array   $attr  Attributes to replace.
     * @param boolean $force Force replacing mode in case we can't read the
     *                       attribute value but are allowed to replace it.
     */
    public function replace(array $attr = array(), $force = false)
    {
        foreach ($attr as $k => $v) {
            $k = $this->_getAttrName($k);
            if (!is_array($v)) {
                /* Delete attributes with empty values; treat integers as
                 * string. */
                if (is_int($v)) {
                    $v = (string)$v;
                }
                if ($v == null) {
                    $this->delete($k);
                    continue;
                } else {
                    $v = array($v);
                }
            }
            /* Existing attributes will get replaced. */
            if ($this->exists($k) || $force) {
                $this->_changes['replace'][$k] = $v;
                $this->_attributes[$k]         = $v;
            } else {
                /* New ones just get added. */
                $this->add(array($k => $v));
            }
        }
    }

    /**
     * Updates the entry on the directory server.
     *
     * This will evaluate all changes made so far and send them to the
     * directory server.
     *
     * If you make changes to objectclasses wich have mandatory attributes set,
     * update() will currently fail. Remove the entry from the server and readd
     * it as new in such cases. This also will deal with problems with setting
     * structural object classes.
     *
     * @todo Entry rename with a DN containing special characters needs testing!
     *
     * @throws Horde_Ldap_Exception
     */
    public function update()
    {
        /* Ensure we have a valid LDAP object. */
        $ldap = $this->getLDAP();

        /* Get and check link. */
        $link = $ldap->getLink();
        if (!is_resource($link)) {
            throw new Horde_Ldap_Exception('Could not update entry: internal LDAP link is invalid');
        }

        /* Delete the entry. */
        if ($this->_delete) {
            return $ldap->delete($this);
        }

        /* New entry. */
        if ($this->_new) {
            $ldap->add($this);
            $this->_new                = false;
            $this->_changes['add']     = array();
            $this->_changes['delete']  = array();
            $this->_changes['replace'] = array();
            $this->_original           = $this->_attributes;
            return;
        }

        /* Rename/move entry. */
        if (!is_null($this->_newdn)) {
            if ($ldap->getVersion() != 3) {
                throw new Horde_Ldap_Exception('Renaming/Moving an entry is only supported in LDAPv3');
            }
            /* Make DN relative to parent (needed for LDAP rename). */
            $parent = Horde_Ldap_Util::explodeDN($this->_newdn, array('casefolding' => 'none', 'reverse' => false, 'onlyvalues' => false));
            $child = array_shift($parent);

            /* Maybe the DN consist of a multivalued RDN, we must build the DN
             * in this case because the $child RDN is an array. */
            if (is_array($child)) {
                $child = Horde_Ldap_Util::canonicalDN($child);
            }
            $parent = Horde_Ldap_Util::canonicalDN($parent);

            /* Rename/move. */
            if (!@ldap_rename($link, $this->_dn, $child, $parent, true)) {
                throw new Horde_Ldap_Exception('Entry not renamed: ' . @ldap_error($link), @ldap_errno($link));
            }

            /* Reflect changes to local copy. */
            $this->_dn    = $this->_newdn;
            $this->_newdn = null;
        }

        /* Carry out modifications to the entry. */
        foreach ($this->_changes['add'] as $attr => $value) {
            /* If attribute exists, add new values. */
            if ($this->exists($attr)) {
                if (!@ldap_mod_add($link, $this->dn(), array($attr => $value))) {
                    throw new Horde_Ldap_Exception('Could not add new values to attribute ' . $attr . ': ' . @ldap_error($link), @ldap_errno($link));
                }
            } else {
                /* New attribute. */
                if (!@ldap_modify($link, $this->dn(), array($attr => $value))) {
                    throw new Horde_Ldap_Exception('Could not add new attribute ' . $attr . ': ' . @ldap_error($link), @ldap_errno($link));
                }
            }
            unset($this->_changes['add'][$attr]);
        }

        foreach ($this->_changes['delete'] as $attr => $value) {
            /* In LDAPv3 you need to specify the old values for deleting. */
            if (is_null($value) && $ldap->getVersion() == 3) {
                $value = $this->_original[$attr];
            }
            if (!@ldap_mod_del($link, $this->dn(), array($attr => $value))) {
                throw new Horde_Ldap_Exception('Could not delete attribute ' . $attr . ': ' . @ldap_error($link), @ldap_errno($link));
            }
            unset($this->_changes['delete'][$attr]);
        }

        foreach ($this->_changes['replace'] as $attr => $value) {
            if (!@ldap_modify($link, $this->dn(), array($attr => $value))) {
                throw new Horde_Ldap_Exception('Could not replace attribute ' . $attr . ' values: ' . @ldap_error($link), @ldap_errno($link));
            }
            unset($this->_changes['replace'][$attr]);
        }

        /* All went well, so $_attributes (local copy) becomes $_original
         * (server). */
        $this->_original = $this->_attributes;
    }

    /**
     * Returns the right attribute name.
     *
     * @param string $attr Name of attribute.
     *
     * @return string The right name of the attribute
     */
    protected function _getAttrName($attr)
    {
        $name = Horde_String::lower($attr);
        return isset($this->_map[$name]) ? $this->_map[$name] : $attr;
    }

    /**
     * Returns a reference to the LDAP-Object of this entry.
     *
     * @return Horde_Ldap  Reference to the Horde_Ldap object (the connection).
     * @throws Horde_Ldap_Exception
     */
    public function getLDAP()
    {
        if (!($this->_ldap instanceof Horde_Ldap)) {
            throw new Horde_Ldap_Exception('ldap property is not a valid Horde_Ldap object');
        }
        return $this->_ldap;
    }

    /**
     * Sets a reference to the LDAP object of this entry.
     *
     * After setting a Horde_Ldap object, calling update() will use that object
     * for updating directory contents. Use this to dynamicly switch
     * directories.
     *
     * @param Horde_Ldap $ldap  Horde_Ldap object that this entry should be
     *                          connected to.
     *
     * @throws Horde_Ldap_Exception
     */
    public function setLDAP(Horde_Ldap $ldap)
    {
        $this->_ldap = $ldap;
    }

    /**
     * Marks the entry as new or existing.
     *
     * If an entry is marked as new, it will be added to the directory when
     * calling {@link update()}.
     *
     * If the entry is marked as old ($mark = false), then the entry is assumed
     * to be present in the directory server wich results in modification when
     * calling {@link update()}.
     *
     * @param boolean $mark Whether to mark the entry as new.
     */
    public function markAsNew($mark = true)
    {
        $this->_new = (bool)$mark;
    }

    /**
     * Applies a regular expression onto a single- or multi-valued attribute
     * (like preg_match()).
     *
     * This method behaves like PHP's preg_match() but with some exception.
     * Since it is possible to have multi valued attributes the $matches
     * array will have a additionally numerical dimension (one for each value):
     * <code>
     * $matches = array(
     *         0 => array (usual preg_match() returned array),
     *         1 => array (usual preg_match() returned array)
     * )
     * </code>
     * $matches will always be initialized to an empty array inside.
     *
     * Usage example:
     * <code>
     * try {
     *     if ($entry->pregMatch('/089(\d+)/', 'telephoneNumber', $matches)) {
     *         // Match of value 1, content of first bracket
     *         echo 'First match: ' . $matches[0][1];
     *     } else {
     *         echo 'No match found.';
     *     }
     * } catch (Horde_Ldap_Exception $e) {
     *     echo 'Error: ' . $e->getMessage();
     * }
     * </code>
     *
     * @param string $regex     The regular expression.
     * @param string $attr_name The attribute to search in.
     * @param array  $matches   Array to store matches in.
     *
     * @return boolean  True if we had a match in one of the values.
     * @throws Horde_Ldap_Exception
     */
    public function pregMatch($regex, $attr_name, &$matches = array())
    {
        /* Fetch attribute values. */
        $attr = $this->getValue($attr_name, 'all');
        unset($attr['count']);

        /* Perform preg_match() on all values. */
        $match = false;
        foreach ($attr as $thisvalue) {
            if (preg_match($regex, $thisvalue, $matches_int)) {
                $match = true;
                array_push($matches, $matches_int);
            }
        }

        return $match;
    }

    /**
     * Returns whether the entry is considered new (not present in the server).
     *
     * This method doesn't tell you if the entry is really not present on the
     * server. Use {@link Horde_Ldap::exists()} to see if an entry is already
     * there.
     *
     * @return boolean  True if this is considered a new entry.
     */
    public function isNew()
    {
        return $this->_new;
    }

    /**
     * Is this entry going to be deleted once update() is called?
     *
     * @return boolean  True if this entry is going to be deleted.
     */
    public function willBeDeleted()
    {
        return $this->_delete;
    }

    /**
     * Is this entry going to be moved once update() is called?
     *
     * @return boolean  True if this entry is going to be move.
     */
    public function willBeMoved()
    {
        return $this->dn() !== $this->currentDN();
    }

    /**
     * Returns always the original DN.
     *
     * If an entry will be moved but {@link update()} was not called, {@link
     * dn()} will return the new DN. This method however, returns always the
     * current active DN.
     *
     * @return string  The current DN
     */
    public function currentDN()
    {
        return $this->_dn;
    }

    /**
     * Returns the attribute changes to be carried out once update() is called.
     *
     * @return array  The due changes.
     */
    public function getChanges()
    {
        return $this->_changes;
    }
}
