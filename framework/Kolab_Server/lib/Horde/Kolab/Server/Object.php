<?php
/**
 * The base class representing Kolab objects stored in the server
 * database.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab db.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object
{
    /** Define types of return values for searches. */
    const RESULT_SINGLE = 1;
    const RESULT_STRICT = 2;
    const RESULT_MANY   = 3;

    /** Define attributes specific to this object type */

    /** The global ID of this object on the server */
    const ATTRIBUTE_UID = 'dn';

    /** The ID part of the UID */
    const ATTRIBUTE_ID = 'id';

    /** The attribute holding the object classes */
    const ATTRIBUTE_OC = 'objectClass';

    /** Define the possible Kolab object classes */
    const OBJECTCLASS_TOP = 'top';

    /**
     * Link into the Kolab server.
     *
     * @var Kolab_Server
     */
    protected $server;

    /**
     * UID of this object on the Kolab server.
     *
     * @var string
     */
    protected $uid;

    /**
     * The cached LDAP result
     *
     * FIXME: Include _ldap here
     *
     * @var mixed
     */
    private $_cache = false;

    /**
     * Cache for derived values. This may not be the same cache as the original
     * value cache as some attributes may have the same name.
     *
     * @var array
     */
    private $_derivative_cache = array();

    /**
     * Does the object exist in the LDAP database?
     *
     * @var boolean
     */
    private $_exists;

    /** FIXME: Add an attribute cache for the get() function */

    /**
     * The LDAP attributes supported by this class.
     *
     * @var array
     */
    public $attributes;

    /**
     * An attribute map for faster access.
     *
     * @var array
     */
    public $attribute_map;

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        /**
         * Attributes that are defined for this object type. It is not
         * guaranteed that this setting takes effect as this module can be
         * configured to only trust the schema returned from the server.
         */
        'defined' => array(
            self::ATTRIBUTE_OC,
        ),
        /**
         * Derived attributes are calculated based on other attribute values.
         */
        'derived' => array(
            self::ATTRIBUTE_ID => array(),
        ),
        /**
         * Attributes that are required for this object type. It is not
         * guaranteed that this setting takes effect as this module can be
         * configured to only trust the schema returned from the server.
         */
        'required' => array(
            self::ATTRIBUTE_OC,
        ),
        /**
         * Default values for attributes without a value.
         */
        'defaults' => array(
        ),
        /**
         * Locked attributes. These are fixed after the object has been stored
         * once. They may not be modified again.
         */
        'locked' => array(
            self::ATTRIBUTE_ID,
            self::ATTRIBUTE_OC,
        ),
        /**
         * The object classes representing this object.
         */
        'object_classes' => array(
            self::OBJECTCLASS_TOP,
        ),
    );

    /**
     * Sort by this attributes (must be a LDAP attribute).
     *
     * @var string
     */
    var $sort_by = self::ATTRIBUTE_UID;

    /**
     * Initialize the Kolab Object. Provide either the UID or a
     * LDAP search result.
     *
     * @param Horde_Kolab_Server &$server The link into the Kolab server.
     * @param string             $uid     UID of the object.
     * @param array              $data    A possible array of data for the object
     */
    public function __construct(&$server, $uid = null, $data = null)
    {
        $this->server = &$server;
        if (empty($uid)) {
            if (empty($data) || !isset($data[self::ATTRIBUTE_UID])) {
                throw new Horde_Kolab_Server_Exception(_('Specify either the UID or a search result!'));
            }
            if (is_array($data[self::ATTRIBUTE_UID])) {
                $this->uid = $data[self::ATTRIBUTE_UID][0];
            } else {
                $this->uid = $data[self::ATTRIBUTE_UID];
            }
            $this->_cache = $data;
        } else {
            $this->uid = $uid;
        }

        list($this->attributes, $this->attribute_map) = $server->getAttributes(get_class($this));
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server_Object instance based on
     * $type.
     *
     * @param mixed  $type     The type of the Horde_Kolab_Server_Object subclass
     *                         to return.
     * @param string $uid      UID of the object
     * @param array  &$storage A link to the Kolab_Server class handling read/write.
     * @param array  $data     A possible array of data for the object
     *
     * @return Horde_Kolab_Server_Object|PEAR_Error The newly created concrete
     *                                 Horde_Kolab_Server_Object instance.
     */
    static public function &factory($type, $uid, &$storage, $data = null)
    {
        $result = Horde_Kolab_Server_Object::loadClass($type);

        if (class_exists($type)) {
            $object = &new $type($storage, $uid, $data);
        } else {
            throw new Horde_Kolab_Server_Exception('Class definition of ' . $type . ' not found.');
        }

        return $object;
    }

    /**
     * Attempts to load the concrete Horde_Kolab_Server_Object class based on
     * $type.
     *
     * @param mixed $type The type of the Horde_Kolab_Server_Object subclass.
     *
     * @static
     *
     * @return true|PEAR_Error True if successfull.
     */
    static public function loadClass($type)
    {
        if (!class_exists($type)) {
            throw new Horde_Kolab_Server_Exception('Class definition of ' . $type . ' not found.');
        }
    }

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return '(&(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_TOP . '))';
    }

    /**
     * Does the object exist?
     *
     * @return NULL
     */
    public function exists()
    {
        if (!isset($this->_exists)) {
            try {
                $this->read();
                $this->_exists = true;
            } catch (Horde_Kolab_Server_Exception $e) {
                $this->_exists = false;
            }
        }
        return $this->_exists;
    }

    /**
     * Read the object into the cache
     *
     * @return NULL
     */
    protected function read()
    {
        if (!empty($this->attributes)) {
            $attributes = array_keys($this->attributes);
        } else {
            $attributes = null;
        }
        $this->_cache = $this->server->read($this->uid,
                                            $attributes);
    }

    /**
     * Get the specified attribute of this object
     *
     * @param string  $attr   The attribute to read
     * @param boolean $single Should only a single attribute be returned?
     *
     * @return string the value of this attribute
     *
     * @todo: This needs to be magic
     */
    public function get($attr, $single = true)
    {
        if ($attr != self::ATTRIBUTE_UID) {
            // FIXME: This wont work this way.
            if (!empty($this->attributes)
                && !in_array($attr, array_keys($this->attributes))
                && !empty($this->attribute_map['derived'])
                && !in_array($attr, $this->attribute_map['derived'])) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                               $attr));
            }
            if (!$this->_cache) {
                $this->read();
            }
        }

        if (!empty($this->attribute_map['derived']) 
            && in_array($attr, $this->attribute_map['derived'])) {
            return $this->derive($attr);
        }

        switch ($attr) {
        case self::ATTRIBUTE_UID:
            return $this->uid;
        default:
            return $this->_get($attr, $single);
        }
    }

    /**
     * Get the specified attribute of this object
     *
     * @param string  $attr   The attribute to read
     * @param boolean $single Should a single value be returned
     *                        or are multiple values allowed?
     *
     * @return string the value of this attribute
     */
    protected function _get($attr, $single = true)
    {
        if (isset($this->_cache[$attr])) {
            if ($single && is_array($this->_cache[$attr])) {
                return $this->_cache[$attr][0];
            } else {
                return $this->_cache[$attr];
            }
        }
        return false;
    }

    /**
     * Derive an attribute value.
     *
     * @param string $attr The attribute to derive.
     *
     * @return mixed The value of the attribute.
     */
    protected function derive($attr)
    {
        switch ($attr) {
        case self::ATTRIBUTE_ID:
            return substr($this->uid, 0,
                          strlen($this->uid) - strlen($this->server->getBaseUid()) - 1);
        default:
            //FIXME: Fill the cache here
            return $this->getField($attr);
        }
    }

    /**
     * Get a derived attribute value.
     *
     * @param string $attr      The attribute to derive.
     * @param string $separator The field separator.
     * @param int    $max_count The maximal number of fields.
     *
     * @return mixed The value of the attribute.
     */
    protected function getField($attr, $separator = '$', $max_count = null)
    {
        $basekey = $this->attributes[$attr]['base'];
        $base = $this->_get($basekey);
        if (empty($base)) {
            return;
        }
        $fields = explode($separator, $base, $max_count);
        return isset($fields[$this->attributes[$attr]['order']]) ? $fields[$this->attributes[$attr]['order']] : null;
    }

    /**
     * Collapse derived values back into the main attributes.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attribute to collapse.
     * @param array  $info       The information currently working on.
     * @param string $separator  Separate the fields using this character.
     *
     * @return NULL.
     */
    protected function collapse($key, $attributes, &$info, $separator = '$')
    {
        switch ($key) {
        default:
            /**
             * Check how many empty entries we have at the end of the array. We
             * may omit these together with their field separators.
             */
            krsort($attributes);
            $empty = count($attributes);
            foreach ($attributes as $attribute) {
                if (empty($info[$attribute])) {
                    $empty--;
                } else {
                    break;
                }
            }
            ksort($attributes);
            $unset = $attributes;
            $result = '';
            for ($i = 0; $i < $empty; $i++) {
                $akey = array_shift($attributes);
                //FIXME: We don't handle multiple values correctly here
                $value = isset($info[$akey]) ? $info[$akey] : '';
                if (is_array($value)) {
                    $value = $value[0];
                }
                $result .= $this->quote($value);
                if ($i != ($empty - 1)) {
                    $result .= $separator;
                }
            }
            foreach ($unset as $attribute) {
                unset($info[$attribute]);
            }

            $info[$key] = $result;
        }
    }

    /**
     * Quote field separaotrs within a LDAP value.
     *
     * @param string $string The string that should be quoted.
     *
     * @return string The quoted string.
     */
    protected function quote($string)
    {
        return str_replace(array('\\',   '$',),
                           array('\\5c', '\\24',),
                           $string);
    }

    /**
     * Unquote a LDAP value.
     *
     * @param string $string The string that should be unquoted.
     *
     * @return string The unquoted string.
     */
    protected function unquote($string)
    {
        return str_replace(array('\\5c', '\\24',),
                           array('\\',   '$',),
                           $string);
    }

    /**
     * Convert the object attributes to a hash.
     *
     * @param string $attrs The attributes to return.
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash($attrs = null)
    {
        $result = array();

        if (isset($attrs)) {
            foreach ($attrs as $key) {
                $value        = $this->get($key);
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get the UID of this object
     *
     * @return string the UID of this object
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string The ID.
     */
    public static function generateId($info)
    {
        if (!empty($info[self::ATTRIBUTE_ID])) {
            return $info[self::ATTRIBUTE_ID];
        }
        return hash('sha256', uniqid(mt_rand(), true));
    }

    /**
     * Saves object information. This may either create a new entry or modify an
     * existing entry.
     *
     * Please note that fields with multiple allowed values require the callee
     * to provide the full set of values for the field. Any old values that are
     * not resubmitted will be considered to be deleted.
     *
     * @param array $info The information about the object.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Server_Exception If saving the data failed.
     */
    public function save($info)
    {
        if (!empty($this->attributes)) {
            foreach ($info as $key => $value) {
                if (!in_array($key, array_keys($this->attributes))) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                                   $key));
                }
            }
        }

        $collapse = array();
        foreach ($this->attribute_map['derived'] as $key) {
            $attribute = $this->attributes[$key];
            if (isset($attribute['base'])
                && isset($attribute['order'])) {
                $collapse[$attribute['base']][$attribute['order']] = $key;
            }
        }

        foreach ($collapse as $key => $attributes) {
            $this->collapse($key, $attributes, $info);
        }

        if (!$this->exists()) {
            foreach ($this->attribute_map['required'] as $key) {
                if (!in_array($key, array_keys($info)) || empty($info[$key])) {
                    if (empty($this->attributes[$key]['default'])) {
                        throw new Horde_Kolab_Server_Exception(sprintf(_("The value for \"%s\" is empty but required!"),
                                                                       $key));
                    } else {
                        $info[$key] = $this->attributes[$key]['default'];
                    }
                }
            }

            $submitted = $info;
            foreach ($submitted as $key => $value) {
                if (empty($value)) {
                    unset($info[$key]);
                }
            }
        } else {
            foreach ($info as $key => $value) {
                if (in_array($key, $this->attribute_map['locked'])) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("The value for \"%s\" may not be modified on an existing object!"),
                                                                   $key));
                }
            }

            $old_keys = array_keys($this->_cache);
            $submitted = $info;
            foreach ($submitted as $key => $value) {
                if (empty($value) && !isset($this->_cache[$key])) {
                    unset($info[$key]);
                    continue;
                }
                if (in_array($key, $old_keys)) {
                    if (!is_array($value) && !is_array($this->_cache[$key])) {
                        if ($value == $this->_cache[$key]) {
                            // Unchanged value
                            unset($info[$key]);
                        }
                    } else {
                        if (!is_array($value)) {
                            $value = array($value);
                            $info[$key] = $value;
                        }
                        if (!is_array($this->_cache[$key])) {
                            $changes = array_diff(array($this->_cache[$key]), $value);
                        } else {
                            $changes = array_diff($this->_cache[$key], $value);
                        }
                        if (empty($changes)) {
                            // Unchanged value
                            unset($info[$key]);
                        }
                    }
                }
            }
        }

        $result = $this->server->save($this->uid, $info, $this->exists());

        $this->_exists = true;
        $this->_cache  = array_merge($this->_cache, $info);

        return $result;
    }

    /**
     * Identify the UID(s) of the result entry(s).
     *
     * @param array $result   The LDAP search result.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception If the number of results did not
     *                                      meet the expectations.
     */
    static protected function uidFromResult($result,
                                            $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        if (empty($result)) {
            return false;
        }
        $uids = array_keys($result);

        switch ($restrict) {
        case self::RESULT_STRICT:
            if (count($uids) > 1) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Found %s results when expecting only one!"),
                                                               $count));
            }
        case self::RESULT_SINGLE:
            return array_pop($uids);
        case self::RESULT_MANY:
            return $uids;
        }
    }

    /**
     * Get the attributes of the result entry(s).
     *
     * @param array $result   The LDAP search result.
     * @param array $attrs    The attributes to retrieve.
     * @param int   $restrict A Horde_Kolab_Server::RESULT_* result restriction.
     *
     * @return array The attributes of the entry(s) found.
     *
     * @throws Horde_Kolab_Server_Exception If the number of results did not
     *                                      meet the expectations.
     */
    static protected function attrsFromResult($result, $attrs,
                                              $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        switch ($restrict) {
        case self::RESULT_STRICT:
            if (count($result) > 1) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Found %s results when expecting only one!"),
                                                               $count));
            }
        case self::RESULT_SINGLE:
            if (count($result) > 0) {
                return array_pop($result);
            }
            return array();
        case self::RESULT_MANY:
            return $result;
        }
        return array();
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSearchOperations()
    {
        $searches = array(
            'basicUidForSearch',
            'attrsForSearch',
        );
        return $searches;
    }

    /**
     * Identify the UID for the first object found using the specified
     * search criteria.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param array              $criteria The search parameters as array.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result
     *                                     restriction.
     *
     * @return boolean|string|array The UID(s) or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function basicUidForSearch($server, $criteria,
                                             $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        $params = array('attributes' => self::ATTRIBUTE_UID);
        $filter = $server->searchQuery($criteria);
        $result = $server->search($filter, $params, $server->getBaseUid());
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
        return self::uidFromResult($data, $restrict);
    }

    /**
     * Identify attributes for the objects found using a filter.
     *
     * @param Horde_Kolab_Server $server   The server to query.
     * @param array              $criteria The search parameters as array.
     * @param array              $attrs    The attributes to retrieve.
     * @param int                $restrict A Horde_Kolab_Server::RESULT_* result
     *                                     restriction.
     *
     * @return array The results.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function attrsForSearch($server, $criteria, $attrs,
                                          $restrict = Horde_Kolab_Server::RESULT_SINGLE)
    {
        $params = array('attributes' => $attrs);
        $filter = $server->searchQuery($criteria);
        $result = $server->search($filter, $params, $server->getBaseUid());
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
        return self::attrsFromResult($data, $attrs, $restrict);
    }
};
