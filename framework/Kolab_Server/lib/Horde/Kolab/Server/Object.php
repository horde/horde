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

    /** Define attributes specific to this object type */

    /** The global ID of this object on the server */
    const ATTRIBUTE_UID          = 'dn';

    /** The ID part of the UID */
    const ATTRIBUTE_ID           = 'id';

    /** The attribute holding the object classes */
    const ATTRIBUTE_OC           = 'objectClass';

    /** Define the possible Kolab object classes */
    const OBJECTCLASS_TOP        = 'top';

    /**
     * Link into the Kolab server.
     *
     * @var Kolab_Server
     */
    protected $db;

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

    /** FIXME: Add an attribute cache for the get() function */

    /**
     * The LDAP attributes supported by this class.
     *
     * @var array
     */
    public $supported_attributes = false;

    /**
     * Attributes derived from other object attributes.
     *
     * @var array
     */
    public $derived_attributes = array(
        self::ATTRIBUTE_ID,
    );

    /**
     * The attributes required when creating an object of this class.
     *
     * @var array
     */
    public $required_attributes = false;

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    protected $object_classes = array(
        self::OBJECTCLASS_TOP
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
     * @param Horde_Kolab_Server &$db  The link into the Kolab db.
     * @param string             $uid  UID of the object.
     * @param array              $data A possible array of data for the object
     */
    public function __construct(&$db, $uid = null, $data = null)
    {
        $this->db = &$db;
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
        try {
            $this->read();
        } catch (Horde_Kolab_Server_Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Read the object into the cache
     *
     * @return NULL
     */
    protected function read()
    {
        $this->_cache = $this->db->read($this->uid,
                                        $this->supported_attributes);
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
            if ($this->supported_attributes !== false
                && !in_array($attr, $this->supported_attributes)
                && !in_array($attr, $this->derived_attributes)) {
                throw new Horde_Kolab_Server_Exception(sprintf(_("Attribute \"%s\" not supported!"),
                                                               $attr));
            }
            if (!$this->_cache) {
                $this->read();
            }
        }

        if (in_array($attr, $this->derived_attributes)) {
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
                          strlen($this->uid) - strlen($this->db->getBaseUid()) - 1);
        }
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
        return hash('sha256', uniqid(mt_rand(), true));
    }

    /**
     * Saves object information.
     *
     * @param array $info The information about the object.
     *
     * @return boolean|PEAR_Error True on success.
     */
    public function save($info)
    {
        if ($this->required_attributes !== false) {
            foreach ($this->required_attributes as $attribute) {
                if (!isset($info[$attribute])) {
                    throw new Horde_Kolab_Server_Exception(sprintf(_("The value for \"%s\" is missing!"),
                                                                   $attribute));
                }
            }
        }

        $info[self::ATTRIBUTE_OC] = $this->object_classes;

        $result = $this->db->save($this->uid, $info);
        if ($result === false || $result instanceOf PEAR_Error) {
            return $result;
        }

        $this->_cache = $info;

        return $result;
    }
};
