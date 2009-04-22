<?php
/**
 * A standard Kolab user.
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
 * This class provides methods to deal with Kolab users stored in
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
class Horde_Kolab_Server_Object_Kolab_User extends Horde_Kolab_Server_Object_Kolabinetorgperson
{

    /** Define attributes specific to this object type */

    /** The user type */
    const ATTRIBUTE_USERTYPE = 'usertype';

    /** The first name */
    const ATTRIBUTE_FN = 'fn';

    /** Define the possible Kolab user types */
    const USERTYPE_STANDARD = 0;
    const USERTYPE_INTERNAL = 1;
    const USERTYPE_GROUP    = 2;
    const USERTYPE_RESOURCE = 3;

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'derived' => array(
            self::ATTRIBUTE_USERTYPE => array(),
            self::ATTRIBUTE_FN => array(),
        ),
        'required' => array(
            self::ATTRIBUTE_USERPASSWORD,
        ),
    );

    /**
     * Initialize the Kolab Object. Provide either the UID or a
     * LDAP search result.
     *
     * @param Horde_Kolab_Server &$db  The link into the Kolab db.
     * @param string             $dn   UID of the object.
     * @param array              $data A possible array of data for the object
     */
    public function __construct(&$db, $dn = null, $data = null)
    {
        global $conf;

        /** Allows to customize the supported user attributes. */
        if (isset($conf['kolab']['server']['user_supported_attrs'])) {
            $this->supported_attributes = $conf['kolab']['server']['user_supported_attrs'];
        }

        /** Allows to customize the required user attributes. */
        if (isset($conf['kolab']['server']['user_required_attrs'])) {
            $this->required_attributes = $conf['kolab']['server']['user_required_attrs'];
        }

        /** Allows to customize the user object classes. */
        if (isset($conf['kolab']['server']['user_objectclasses'])) {
            $this->object_classes = $conf['kolab']['server']['user_object_classes'];
        }

        parent::__construct($db, $dn, $data);
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
        $criteria = array('AND' => array(
                              array('field' => self::ATTRIBUTE_SN,
                                    'op'    => '=',
                                    'test'  => '*'),
                              array('field' => self::ATTRIBUTE_MAIL,
                                    'op'    => '=',
                                    'test'  => '*'),
                              array('field' => self::ATTRIBUTE_SID,
                                    'op'    => '=',
                                    'test'  => '*'),
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_KOLABINETORGPERSON),
                          ),
        );
        return $criteria;
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
        case self::ATTRIBUTE_USERTYPE:
            if (strpos($this->_uid, 'cn=internal')) {
                return self::USERTYPE_INTERNAL;
            } else if (strpos($this->_uid, 'cn=group')) {
                return self::USERTYPE_GROUP;
            } else if (strpos($this->_uid, 'cn=resource')) {
                return self::USERTYPE_RESOURCE;
            } else {
                return self::USERTYPE_STANDARD;
            }
        case self::ATTRIBUTE_FN:
            return $this->getFn();
        default:
            return parent::derive($attr);
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
        if (!isset($attrs)) {
            $attrs = array(
                self::ATTRIBUTE_SID,
                self::ATTRIBUTE_FN,
                self::ATTRIBUTE_MAIL,
                self::ATTRIBUTE_USERTYPE,
            );
        }
        return parent::toHash($attrs);
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
     * @return mixed|PEAR_Error An array of group ids, false if no groups were
     *                          found.
     */
    public function getGroups()
    {
        return $this->server->getGroups($this->uid);
    }

    /**
     * Returns the server url of the given type for this user.
     *
     * This method is used to encapsulate multidomain support.
     *
     * @param string $server_type The type of server URL that should be returned.
     *
     * @return string The server url or empty on error.
     */
    public function getServer($server_type)
    {
        global $conf;

        switch ($server_type) {
        case 'freebusy':
            $server = $this->get(self::ATTRIBUTE_FREEBUSYHOST);
            if (!empty($server)) {
                return $server;
            }
            if (isset($conf['kolab']['freebusy']['server'])) {
                return $conf['kolab']['freebusy']['server'];
            }
            $server = $this->getServer('homeserver');
            if (empty($server)) {
                $server = $_SERVER['SERVER_NAME'];
            }
            if (isset($conf['kolab']['server']['freebusy_url_format'])) {
                return sprintf($conf['kolab']['server']['freebusy_url_format'],
                               $server);
            } else {
                return 'https://' . $server . '/freebusy';
            }
        case 'imap':
            $server = $this->get(self::ATTRIBUTE_IMAPHOST);
            if (!empty($server)) {
                return $server;
            }
        case 'homeserver':
        default:
            $server = $this->get(self::ATTRIBUTE_HOMESERVER);
            if (empty($server)) {
                $server = $_SERVER['SERVER_NAME'];
            }
            return $server;
        }
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
    public function generateId($info)
    {
        global $conf;

        /** The fields that should get mapped into the user ID. */
        if (isset($conf['kolab']['server']['user_id_mapfields'])) {
            $id_mapfields = $conf['kolab']['server']['user_id_mapfields'];
        } else {
            $id_mapfields = array(self::ATTRIBUTE_GIVENNAME,
                                  self::ATTRIBUTE_SN);
        }

        /** The user ID format. */
        if (isset($conf['kolab']['server']['user_id_format'])) {
            $id_format = $conf['kolab']['server']['user_id_format'];
        } else {
            $id_format    = self::ATTRIBUTE_CN . '=' . '%s %s';
        }

        $fieldarray = array();
        foreach ($id_mapfields as $mapfield) {
            if (isset($info[$mapfield])) {
                $id = $info[$mapfield];
                if (is_array($id)) {
                    $id = $id[0];
                }
                $fieldarray[] = $this->server->structure->quoteForUid($id);
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
     *
     * @throws Horde_Kolab_Server_Exception If the information to be saved is
     *                                      invalid.
     */
    public function save($info = null)
    {
        if (!$this->exists()) {
            if (!isset($info['cn'])) {
                if (!isset($info['sn']) || !isset($info['givenName'])) {
                    throw new Horde_Kolab_Server_Exception(_("Either the last name or the given name is missing!"));
                } else {
                    $info['cn'] = $this->generateId($info);
                }
            }
        }

        if (isset($conf['kolab']['server']['user_mapping'])) {
            $mapped = array();
            $map    = $conf['kolab']['server']['user_mapping'];
            foreach ($map as $key => $val) {
                $mapped[$val] = $info[$key];
            }
            $info = $mapped;
        }

        if (isset($conf['kolab']['server']['user_mapping'])) {
            $mapped = array();
            $map    = $conf['kolab']['server']['user_mapping'];
            foreach ($map as $key => $val) {
                $mapped[$val] = $info[$key];
            }
            $info = $mapped;
        }

        return parent::save($info);
    }
};
