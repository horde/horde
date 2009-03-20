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
    const ATTRIBUTE_UID          = 'dn';
    const ATTRIBUTE_ID           = 'id';
    const ATTRIBUTE_SID          = 'uid';
    const ATTRIBUTE_CN           = 'cn';
    const ATTRIBUTE_SN           = 'sn';
    const ATTRIBUTE_GIVENNAME    = 'givenName';
    const ATTRIBUTE_FN           = 'fn';
    const ATTRIBUTE_MAIL         = 'mail';
    const ATTRIBUTE_DELEGATE     = 'kolabDelegate';
    const ATTRIBUTE_MEMBER       = 'member';
    const ATTRIBUTE_VISIBILITY   = 'visible';
    const ATTRIBUTE_LNFN         = 'lnfn';
    const ATTRIBUTE_FNLN         = 'fnln';
    const ATTRIBUTE_DOMAIN       = 'domain';
    const ATTRIBUTE_DELETED      = 'kolabDeleteFlag';
    const ATTRIBUTE_FBPAST       = 'kolabFreeBusyPast';
    const ATTRIBUTE_FBFUTURE     = 'kolabFreeBusyFuture';
    const ATTRIBUTE_FOLDERTYPE   = 'kolabFolderType';
    const ATTRIBUTE_HOMESERVER   = 'kolabHomeServer';
    const ATTRIBUTE_FREEBUSYHOST = 'kolabFreeBusyServer';
    const ATTRIBUTE_IMAPHOST     = 'kolabImapServer';
    const ATTRIBUTE_IPOLICY      = 'kolabInvitationPolicy';

    /** Define the possible Kolab object classes */
    const OBJECTCLASS_TOP                = 'top';
    const OBJECTCLASS_INETORGPERSON      = 'inetOrgPerson';
    const OBJECTCLASS_KOLABINETORGPERSON = 'kolabInetOrgPerson';
    const OBJECTCLASS_HORDEPERSON        = 'hordePerson';
    const OBJECTCLASS_KOLABGROUPOFNAMES  = 'kolabGroupOfNames';
    const OBJECTCLASS_KOLABSHAREDFOLDER  = 'kolabSharedFolder';

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
     * The LDAP filter to retrieve this object type.
     *
     * @var string
     */
    public static $filter = '';

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var string
     */
    protected $required_group;

    /**
     * The LDAP attributes supported by this class.
     *
     * @var array
     */
    public $supported_attributes = false;

    /**
     * Attributes derived from the LDAP values.
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
    protected $object_classes = array();

    /**
     * Sort by this attributes (must be a LDAP attribute).
     *
     * @var string
     */
    var $sort_by = self::ATTRIBUTE_SN;

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
    public function &factory($type, $uid, &$storage, $data = null)
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
    public static function loadClass($type)
    {
        if (!class_exists($type)) {
            throw new Horde_Kolab_Server_Exception('Class definition of ' . $type . ' not found.');
        }
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
        case self::ATTRIBUTE_FN:
            return $this->getFn();
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
            $result = split(',', $this->uid);
            if (substr($result[0], 0, 3) == 'cn=') {
                return substr($result[0], 3);
            } else {
                return $result[0];
            }
        case self::ATTRIBUTE_LNFN:
            $gn = $this->_get(self::ATTRIBUTE_GIVENNAME, true);
            $sn = $this->_get(self::ATTRIBUTE_SN, true);
            return sprintf('%s, %s', $sn, $gn);
        case self::ATTRIBUTE_FNLN:
            $gn = $this->_get(self::ATTRIBUTE_GIVENNAME, true);
            $sn = $this->_get(self::ATTRIBUTE_SN, true);
            return sprintf('%s %s', $gn, $sn);
        default:
            return false;
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
     * Get the "first name" attribute of this object
     *
     * FIXME: This should get refactored to be combined with the Id value.
     *
     * @return string the "first name" of this object
     */
    protected function getFn()
    {
        $sn = $this->_get(self::ATTRIBUTE_SN, true);
        $cn = $this->_get(self::ATTRIBUTE_CN, true);
        return trim(substr($cn, 0, strlen($cn) - strlen($sn)));
    }

    /**
     * Get the groups for this object
     *
     * @return mixed An array of group ids or a PEAR Error in case of
     *               an error.
     */
    public function getGroups()
    {
        return array();
    }

    /**
     * Returns the server url of the given type for this user.
     *
     * This method can be used to encapsulate multidomain support.
     *
     * @param string $server_type The type of server URL that should be returned.
     *
     * @return string|PEAR_Error The server url or empty.
     */
    public function getServer($server_type)
    {
        throw new Horde_Kolab_Server_Exception('Not implemented!');
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public static function generateId($info)
    {
        $id_mapfields = array('givenName', 'sn');
        $id_format    = '%s %s';

        $fieldarray = array();
        foreach ($id_mapfields as $mapfield) {
            if (isset($info[$mapfield])) {
                $fieldarray[] = $info[$mapfield];
            } else {
                $fieldarray[] = '';
            }
        }

        return trim(vsprintf($id_format, $fieldarray), " \t\n\r\0\x0B,");
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

        $info['objectClass'] = $this->object_classes;

        $result = $this->db->save($this->uid, $info);
        if ($result === false || is_a($result, 'PEAR_Error')) {
            return $result;
        }

        $this->_cache = $info;

        return $result;
    }
};
