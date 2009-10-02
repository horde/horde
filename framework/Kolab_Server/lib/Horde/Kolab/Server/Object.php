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

    /** The time the object was created */
    const ATTRIBUTE_CREATIONDATE = 'createTimestamp';

    /** The time the object was last modified */
    const ATTRIBUTE_MODIFICATIONDATE = 'modifyTimestamp';

    /** The time the object was created */
    const ATTRDATE_CREATIONDATE = 'createTimestampDate';

    /** The time the object was last modified */
    const ATTRDATE_MODIFICATIONDATE = 'modifyTimestampDate';

    /** Access rules for this object */
    const ATTRIBUTE_ACI = 'OpenLDAPaci';

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
    private $_exists = false;

    /**
     * A cache for the list of actions this object supports.
     *
     * @var array
     */
    protected $_actions;

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
            self::ATTRIBUTE_CREATIONDATE,
            self::ATTRIBUTE_MODIFICATIONDATE,
            self::ATTRIBUTE_ACI,
        ),
        /**
         * Derived attributes are calculated based on other attribute values.
         */
        'derived' => array(
            self::ATTRIBUTE_UID => array(
                'method' => 'getUid',
            ),
            self::ATTRIBUTE_ID => array(
                'method' => 'getId',
            ),
            self::ATTRDATE_CREATIONDATE => array(
                'method' => 'getDate',
                'args' => array(
                    self::ATTRIBUTE_CREATIONDATE,
                ),
            ),
            self::ATTRDATE_MODIFICATIONDATE => array(
                'method' => 'getDate',
                'args' => array(
                    self::ATTRIBUTE_MODIFICATIONDATE,
                ),
            ),
        ),
        /**
         * Attributes that are written using the information from several other
         * attributes.
         */
        'collapsed' => array(
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
    public function __construct(&$server, $uid = null, $data = false)
    {
        $this->server = &$server;
        if (empty($uid)) {
            if (isset($data[self::ATTRIBUTE_UID])) {
                if (is_array($data[self::ATTRIBUTE_UID])) {
                    $this->uid = $data[self::ATTRIBUTE_UID][0];
                } else {
                    $this->uid = $data[self::ATTRIBUTE_UID];
                }
            } else {
                $this->uid = $this->server->generateServerUid(get_class($this),
                                                              $this->generateId($data),
                                                              $data);
            }
        } else {
            $this->uid = $uid;
        }
        $this->_cache = $data;

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
            $object = new $type($storage, $uid, $data);
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
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_OC,
                                               'op'    => '=',
                                               'test'  => self::OBJECTCLASS_TOP),
                          ),
        );
        return $criteria;
    }

    /**
     * Does the object exist?
     *
     * @return NULL
     */
    public function exists()
    {
        try {
            return $this->read();
        } catch (Horde_Kolab_Server_Exception $e) {
            return false;
        }
    }

    /**
     * Read the object into the cache
     *
     * @return NULL
     */
    protected function read()
    {
        if (empty($this->uid)) {
            return false;
        }
        if (empty($this->_exists)) {
            if (!empty($this->attributes)) {
                $attributes = array_keys($this->attributes);
            } else {
                $attributes = null;
            }
            $result = $this->server->read($this->uid,
                                          $attributes);
            /**
             * If reading the server data was unsuccessfull we should keep the
             * initial data in our cache. If reading was successfull we should
             * merge with the initial cache setting.
             */
            if (is_array($this->_cache) && is_array($result)) {
                $this->_cache = array_merge($this->_cache, $result);
            } else if (!is_array($this->_cache)) {
                $this->_cache = $result;
            }
            $this->_exists = true;
        }
        return $this->_exists;
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
        if (!empty($this->attributes)
            && !in_array($attr, array_keys($this->attributes))
            && !empty($this->attribute_map['derived'])
            && !in_array($attr, $this->attribute_map['derived'])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                           $attr));
        }

        $this->exists();

        if (!empty($this->attribute_map['derived'])
            && in_array($attr, $this->attribute_map['derived'])) {
            return $this->derive($attr);
        }

        return $this->_get($attr, $single);
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
        if (isset($this->attributes[$attr]['method'])) {
            if (isset($this->attributes[$attr]['args'])) {
                $args = $this->attributes[$attr]['args'];
            } else {
                $args = array();
            }
            //FIXME: Fill the cache here
            return call_user_func_array(array($this,
                                              $this->attributes[$attr]['method']),
                                        $args);
        }
        return false;
    }

    /**
     * Collapse derived values back into the main attributes.
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attributes to collapse.
     * @param array  $info       The information currently working on.
     *
     * @return NULL.
     */
    protected function collapse($key, $attributes, &$info)
    {
        $changes = false;
        foreach ($attributes['base'] as $attribute) {
            if (isset($info[$attribute])) {
                $changes = true;
                break;
            }
        }
        if ($changes) {
            if (isset($attributes['method'])) {
                $args = array($key, $attributes['base'], &$info);
                if (isset($attributes['args'])) {
                    $args = array_merge($args, $attributes['args']);
                }
                //FIXME: Fill the cache here
                return call_user_func_array(array($this,
                                                 $attributes['method']),
                                           $args);
            }
        }
    }

    /**
     * Quote field separators within a LDAP value.
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
     * @param string $attrs   The attributes to return.
     * @param boolean $single Should only a single attribute be returned?
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash($attrs = null, $single = true)
    {
        $result = array();

        /**
         * Return all supported attributes if no specific attributes were
         * requested.
         */
        if (empty($attrs)) {
            $attrs = array_keys($this->attributes);
        }

        foreach ($attrs as $key) {
            $value        = $this->get($key, $single);
            $result[$key] = $value;
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
     * Get the parent UID of this object
     *
     * @return string the parent UID of this object
     */
    public function getParentUid($level = 1, $uid = null)
    {
        if (empty($uid)) {
            $uid = $this->uid;
        }

        $base = Net_LDAP2_Util::ldap_explode_dn($uid,
                                                array('casefold' => 'none',
                                                      'reverse' => false,
                                                      'onlyvalues' => false));
        if ($base instanceOf PEAR_Error) {
            throw new Horde_Kolab_Server_Exception($base,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        $id = array_shift($base);
        $parent = Net_LDAP2_Util::canonical_dn($base, array('casefold' => 'none'));
        if ($parent instanceOf PEAR_Error) {
            throw new Horde_Kolab_Server_Exception($parent,
                                                   Horde_Kolab_Server_Exception::SYSTEM);
        }
        $level--;
        if ($level == 0) {
            return $parent;
        } else {
            return $this->getParentUid($level, $parent);
        }
    }

    /**
     * Get the ID of this object
     *
     * @return string the ID of this object
     */
    public function getId()
    {
        return substr($this->uid, 0,
                      strlen($this->uid) - strlen($this->server->getBaseUid()) - 1);
    }

    /**
     * Get a derived attribute value by returning a given position in a
     * delimited string.
     *
     * @param string $basekey   Name of the attribute that holds the
     *                          delimited string.
     * @param string $field     The position of the field to retrieve.
     * @param string $separator The field separator.
     * @param int    $max_count The maximal number of fields.
     *
     * @return mixed The value of the attribute.
     */
    protected function getField($basekey, $field = 0, $separator = '$', $max_count = null)
    {
        $base = $this->_get($basekey);
        if (empty($base)) {
            return;
        }
        if (!empty($max_count)) {
            $fields = explode($separator, $base, $max_count);
        } else {
            $fields = explode($separator, $base);
        }
        return isset($fields[$field]) ? $this->unquote($fields[$field]) : null;
    }

    /**
     * Get a derived attribute date by converting it into a Horde_Date object.
     *
     * @param string $key   Name of the attribute that holds the
     *                      date.
     *
     * @return mixed A Horde_Date object or false if the date was not
     *               converted successfully.
     */
    protected function getDate($key)
    {
        $date = $this->_get($key);
        if (empty($date) || !class_exists('Horde_Date')) {
            return false;
        }

        $result = new Horde_Date($date);
        return $result;
    }

    /**
     * Set a collapsed attribute value.
     *
     * @param string  $key        The attribute to collapse into.
     * @param array   $attributes The attributes to collapse.
     * @param array   $info       The information currently working on.
     * @param string  $separator  Separate the fields using this character.
     * @param boolean $unset      Unset the base values.
     *
     * @return NULL.
     */
    protected function setField($key, $attributes, &$info, $separator = '$', $unset = true)
    {
        /**
         * Check how many empty entries we have at the end of the array. We
         * may omit these together with their field separators.
         */
        krsort($attributes);
        $empty = true;
        $end   = count($attributes);
        foreach ($attributes as $attribute) {
            /**
             * We do not expect the callee to always provide all attributes
             * required for a collapsed attribute. So it is necessary to check
             * for old values here.
             */
            if (!isset($info[$attribute])) {
                $old = $this->get($attribute);
                if (!empty($old)) {
                    $info[$attribute] = $old;
                }
            }
            if ($empty && empty($info[$attribute])) {
                $end--;
            } else {
                $empty = false;
            }
        }
        if ($empty) {
            return;
        }
        ksort($attributes);
        $unset = $attributes;
        $result = '';
        for ($i = 0; $i < $end; $i++) {
            $akey = array_shift($attributes);
            $value =  $info[$akey];
            if (is_array($value)) {
                $value = $value[0];
            }
            $result .= $this->quote($value);
            if ($i != ($end - 1)) {
                $result .= $separator;
            }
        }
        if ($unset === true) {
            foreach ($unset as $attribute) {
                unset($info[$attribute]);
            }
        }
        $info[$key] = $result;
    }

    /**
     * Simply remove an attribute so that it does not get transported
     * to the server (it might have been needed before e.g. ID
     * generation).
     *
     * @param string $key        The attribute to collapse into.
     * @param array  $attributes The attributes to collapse.
     * @param array  $info       The information currently working on.
     *
     * @return NULL.
     */
    protected function removeAttribute($key, $attributes, &$info)
    {
        foreach ($attributes as $attribute) {
            unset($info[$attribute]);
        }
    }

    /**
     * Get an empty value
     *
     * @return string An empty string.
     */
    public function getEmpty()
    {
        return '';
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @return string The ID.
     */
    public function generateId($info)
    {
        if (!empty($info[self::ATTRIBUTE_ID])) {
            if (is_array($info[self::ATTRIBUTE_ID])) {
                $id = $info[self::ATTRIBUTE_ID][0];
            } else {
                $id = $info[self::ATTRIBUTE_ID];
            }
            return $this->server->structure->quoteForUid($id);
        }
        return $this->server->structure->quoteForUid(hash('sha256', uniqid(mt_rand(), true)));
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
    public function save($info = null)
    {
        $info = $this->prepareInformation($info);

        $changes = $this->prepareChanges($info);

        $server = $this->server->getMaster();

        $result = $server->save($this->uid, $changes, $this->exists());

        if (!$this->_exists) {
            $this->_exists = true;
            $this->_cache  = $info;
        } else {
            $this->_cache  = array_merge($this->_cache, $info);
        }

        return $result;
    }

    /**
     * Delete this object.
     *
     * @return boolean True if deleting the object succeeded.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function delete()
    {
        $server = $this->server->getMaster();

        return $server->delete($this->uid);
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return array The set of information.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareInformation($info)
    {
        if (empty($info)) {
            /**
             * If no data to save has been provided the object might have been
             * created with initial data. This would have been stored in the
             * cache and should be written now.
             */
            if (!empty($this->_cache)) {
                $info = $this->_cache;
                $this->_cache = false;
            } else {
                return;
            }
        }

        $this->prepareObjectInformation($info);

        if (!empty($this->attributes)) {
            foreach ($info as $key => $value) {
                if (!in_array($key, array_keys($this->attributes))) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                                   $key));
                }
            }
        }

        if (!$this->exists()) {
            foreach ($this->attribute_map['required'] as $key) {
                if (!in_array($key, array_keys($info)) || $info[$key] === null
                    || $info[$key] === '') {
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
                if ($value === null || $info[$key] === '') {
                    unset($info[$key]);
                }
            }
        } else {
            $all_keys = array_keys($this->attributes);
            $submitted = $info;
            foreach ($submitted as $key => $value) {
                /**
                 * Empty values are ignored in case there is no old value stored
                 * or the value is locked. If there is an old value we must
                 * assume the value was removed.
                 */
                $old = $this->get($key, false);
                if (($value === null || $info[$key] === '')
                    && (empty($old)
                        || in_array($key, $this->attribute_map['locked']))) {
                    unset($info[$key]);
                    continue;
                }

                if (in_array($key, $all_keys)) {
                    if (!is_array($value) && !is_array($old)) {
                        if ($value === $old) {
                            // Unchanged value
                            unset($info[$key]);
                        }
                    } else {
                        if (!is_array($value)) {
                            $value = array($value);
                            $info[$key] = $value;
                        }
                        if (!is_array($old)) {
                            $old = array($old);
                        }
                        $changes = $this->getArrayChanges($old, $value);
                        if (empty($changes)) {
                            // Unchanged value
                            unset($info[$key]);
                        }
                    }
                }
            }

            /**
             * This ensures that we did not change anything that is locked after creating the element.
             */
            foreach ($info as $key => $value) {
                if (in_array($key, $this->attribute_map['locked'])) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("The value for \"%s\" may not be modified on an existing object!"),
                                                                   $key));
                }
            }

            /* Check for potential renaming of the object here */
            $new_id = $this->generateId($info);
            if ($new_id !== false) {
                $new_uid = $this->server->generateServerUid(get_class($this),
                                                            $new_id,
                                                            $info);
                if ($new_uid != $this->uid) {
                    $this->server->rename($this->uid, $new_uid);
                    $this->uid = $new_uid;
                }
            }
        }

        foreach ($this->attribute_map['collapsed'] as $key => $attributes) {
            if ($attributes !== false) {
                $this->collapse($key, $attributes, $info);
            }
        }
        return $info;
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(&$info)
    {
    }

    /**
     * Prepare the server changes before saving.
     *
     * @param array $info The information to store.
     *
     * @return array The set of changes. Ready for saving.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareChanges($info)
    {
        if (!empty($this->attributes)) {
            foreach ($info as $key => $value) {
                if (!in_array($key, array_keys($this->attributes))) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                                   $key));
                }
            }
        }

        $changes = array();
        if (!$this->exists()) {
            $changes['add'] = $info;
        } else {
            $all_keys = array_keys($this->attributes);
            foreach ($info as $key => $value) {
                $old = $this->_get($key, false);
                if (is_array($value) && count($value) == 1) {
                    $value = $value[0];
                }
                if (is_array($old) && count($old) == 1) {
                    $old = $old[0];
                }
                if ($old === false && !($value === null || $value === '' || $value === array())) {
                    $changes['add'][$key] = $value;
                    $changes['attributes'][] = $key;
                } else if ($old !== false && ($value === null || $value === '' || $value === array())) {
                    $changes['delete'][] = $key;
                    $changes['attributes'][] = $key;
                } else if (is_array($old) || is_array($value)) {
                    if (!is_array($old)) {
                        $old = array($old);
                    }
                    if (!is_array($value)) {
                        $value = array($value);
                    }
                    $adds = array_diff($value, $old);
                    if (!empty($adds)) {
                        $changes['add'][$key] = $adds;
                        $changes['attributes'][] = $key;
                    }
                    $deletes = array_diff($old, $value);
                    if (!empty($deletes)) {
                        $changes['delete'][$key] = $deletes;
                        $changes['attributes'][] = $key;
                    }
                } else {
                    $changes['replace'][$key] = $value;
                    $changes['attributes'][] = $key;
                }
            }
        }

        if (!empty($changes['attributes'])) {
            $changes['attributes'] = array_unique($changes['attributes']);
        }

        return $changes;
    }

    /**
     * Identify changes between two arrays.
     *
     * @param array $a1 The first array.
     * @param array $a2 The second array.
     *
     * @return array The differences between both arrays.
     */
    public function getArrayChanges($a1, $a2)
    {
        if (empty($a1) || empty($a2)) {
            return !empty($a1) ? $a1 : $a2;
        }
        if (count($a1) != count($a2)) {
            $intersection = array_intersect($a1, $a2);
            return array_merge(array_diff_assoc($a1, $intersection),
                               array_diff_assoc($a2, $intersection));
        }
        $ar = array();
        foreach ($a2 as $k => $v) {
            if (!is_array($v) || !is_array($a1[$k])) {
                if ($v !== $a1[$k]) {
                    $ar[$k] = $v;
                }
            } else {
                if ($arr = $this->getArrayChanges($a1[$k], $a2[$k])) {
                    $ar[$k] = $arr;
                }
            }
        }
        return $ar;
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
            'objectsForUid',
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
        $data = $server->search($filter, $params, $server->getBaseUid());
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
        $data   = $server->search($filter, $params, $server->getBaseUid());
        return self::attrsFromResult($data, $attrs, $restrict);
    }

    /**
     * Returns the UIDs of the sub objects of the given object class for the
     * object with the given uid.
     *
     * @param Horde_Kolab_Server $server The server to query.
     * @param string             $uid    Returns subobjects for this uid.
     * @param string             $oc     Objectclass of the objects to search.
     *
     * @return mixed The UIDs or false if there was no result.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    static public function objectsForUid($server, $uid, $oc)
    {
        $params   = array('attributes' => self::ATTRIBUTE_UID);
        $criteria = array('AND' => array(array('field' => self::ATTRIBUTE_OC,
                                               'op'    => '=',
                                               'test'  => $oc),
                          ),
        );
        $filter = $server->searchQuery($criteria);
        $result = $server->search($filter, $params, $uid);
        return self::uidFromResult($result, Horde_Kolab_Server_Object::RESULT_MANY);
    }

    /**
     * Returns the set of actions supported by this object type.
     *
     * @return array An array of supported actions.
     */
    public function getActions()
    {
        if (!isset($this->_actions)) {
            $this->_actions = $this->_getActions();
        }
        return $this->_actions;
    }

    /**
     * Returns the set of actions supported by this object type.
     *
     * @return array An array of supported actions.
     */
    protected function _getActions()
    {
        return array();
    }

};
