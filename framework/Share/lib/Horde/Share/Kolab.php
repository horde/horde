<?php
/**
 * @package Horde_Share
 */

/**
 * Horde_Share_kolab:: provides the kolab backend for the horde share driver.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
class Horde_Share_kolab extends Horde_Share
{
    const VERSION = 1;

    /**
     * Our Kolab folder list handler
     *
     * @var Kolab_List
     */
    protected $_list;

    /**
     * The share type
     *
     * @var string
     */
    protected $_type;

    /**
     * A marker for the validity of the list cache
     *
     * @var int
     */
    protected $_listCacheValidity;

    /**
     * The session handler.
     *
     * @var Horde_Kolab_Session
     */
    protected $_session;


    private function _getFolderType($app)
    {
        switch ($app) {
        case 'mnemo':
            return 'note';
        case 'kronolith':
            return 'event';
        case 'turba':
            return 'contact';
        case 'nag':
            return 'task';
        default:
            throw new Horde_Share_Exception(sprintf(Horde_Share_Translation::t("The Horde/Kolab integration engine does not support \"%s\""), $app));
        }
    }

//    /**
//     * Returns the properties that need to be serialized.
//     *
//     * @return array  List of serializable properties.
//     */
//    public function __sleep()
//    {
//        $properties = get_object_vars($this);
//        unset($properties['_sortList'], $properties['_list']);
//        $properties = array_keys($properties);
//        return $properties;
//    }
//    /**
//     * Initializes the object.
//     *
//     * @throws Horde_Exception
//     */
//    public function __wakeup()
//    {
//        if (empty($GLOBALS['conf']['kolab']['enabled'])) {
//            throw new Horde_Exception('You must enable the kolab settings to use the Kolab Share driver.');
//        }
//        $this->_type = $this->_getFolderType($this->_app);
//        $this->_list = $GLOBALS['injector']->getInstance('Horde_Kolab_Storage');
//
//        parent::__wakeup();
//    }

    /**
     * Serialize the object. You *MUST* call setStorage() after unserialized.
     *
     * @return string
     */
    public function serialize()
    {
        $data = array(
            self::VERSION,
            $this->_app,
            $this->_root,
            $this->_cache,
            $this->_shareMap,
            $this->_listcache,
            $this->_shareObject,
            $this->_permsObject,
            $this->_type,
            $this->_listCacheValidity,
            $this->_session);

        return serialize($data);
    }

    /**
     * Reconstruct object from serialized data. You MUST call setStorage()
     * after unserialize.
     *
     * @param <type> $data
     */
    public function unserialize($data)
    {
        // Rebuild the object
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }
        $this->_app = $data[1];
        $this->_root = $data[2];
        $this->_cache = $data[3];
        $this->_shareMap = $data[4];
        $this->_listcache = $data[5];
        $this->_shareObject = $data[6];
        $this->_permsObject = $data [7];
        $this->_type = $data[8];
        $this->_listCacheValidity = $data[9];
        $this->_session = $data[10];

        foreach (array_keys($this->_cache) as $name) {
            $this->_initShareObject($this->_cache[$name]);
        }
    }

    /**
     * (re)connect the share object to this share driver. Userful for when
     * share objects are unserialized from a cache separate from the share
     * driver.
     *
     * @param Horde_Share_Object $object
     */
    public function initShareObject($object)
    {
        $object->setShareOb($this);
    }

    /**
     * Set the kolab storage backend.
     *
     * @param Horde_Kolab_Storage $driver
     */
    public function setStorage(Horde_Kolab_Storage $driver)
    {
        $this->_list = $driver;
    }

    public function getStorage()
    {
        return $this->_list;
    }

    /**
     * Returns a Horde_Share_Object_kolab object of the request folder.
     *
     * @param string $object  The share to fetch.
     *
     * @return Horde_Share_Object_kolab  The share object.
     * @throws Horde_Share_Exception
     */
    protected function _getShare($object)
    {
        if (empty($object)) {
            throw new Horde_Share_Exception('No object requested.');
        }

        /** Get the corresponding folder for this share ID */
        $folder = $this->_list->getByShare($object, $this->_type);

        /** Does the folder exist? */
        if (!$folder->exists()) {
            throw new Horde_Share_Exception(sprintf(Horde_Share_Translation::t("Share \"%s\" does not exist."), $object));
        }

        /** Create the object from the folder */
        $share = new Horde_Share_Object_Kolab($object, $this->_type);
        $share->setFolder($folder);

        return $share;
    }

    /**
     * Returns a Horde_Share_Object_kolab object of the requested folder.
     *
     * @param string $id  The id of the share to fetch.
     *
     * @return Horde_Share_Object_kolab  The share object.
     */
    protected function &_getShareById($id)
    {
        return $this->_getShare($id);
    }

    /**
     * Returns an array of Horde_Share_Object_kolab objects corresponding to
     * the requested folders.
     *
     * @param string $ids  The ids of the shares to fetch.
     *
     * @return array  An array of Horde_Share_Object_kolab objects.
     */
    protected function &_getShares($ids)
    {
        $objects = array();
        foreach ($ids as $id) {
            $result = &$this->_getShare($id);
            if ($result instanceof PEAR_Error) {
                return $result;
            }
            $objects[$id] = &$result;
        }
        return $objects;
    }

    /**
     * Lists *all* shares for the current app/share, regardless of
     * permissions.
     *
     * Currently not implemented in this class.
     *
     * @return array  All shares for the current app/share.
     */
    protected function &_listAllShares()
    {
        $shares = array();
        return $shares;
    }

    /**
     * Returns an array of all shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param array  $params  @see Horde_Share::listShares
     *
     * @return array  The shares the user has access to.
     */
    protected function _listShares($userid, $params = array())
    {
        $key = serialize(array($this->_type, $userid, $params['perm'], $params['attributes']));
        if ($this->_list === false) {
            $this->_listCache[$key] = array();
        } else if (empty($this->_listCache[$key])
            || $this->_list->validity != $this->_listCacheValidity) {
            $sharelist = $this->_list->getByType($this->_type);
            if ($sharelist instanceof PEAR_Error) {
                throw new Horde_Share_Exception($sharelist->getMessage());
            }

            $shares = array();
            foreach ($sharelist as $folder) {
                $id = $folder->getShareId();
                $share = $this->getShare($id);
                $keep = true;
                if (!$share->hasPermission($userid, $params['perm'])) {
                    $keep = false;
                }
                if (isset($params['attributes']) && $keep) {
                    if (is_array($params['attributes'])) {
                        foreach ($params['attributes'] as $key => $value) {
                            if (!$share->get($key) == $value) {
                                $keep = false;
                                break;
                            }
                        }
                    } elseif (!$share->get('owner') == $params['attributes']) {
                        $keep = false;
                    }
                }
                if ($keep) {
                    $shares[] = $id;
                }
            }
            $this->_listCache[$key] = $shares;
            $this->_listCacheValidity = $this->_list->validity;
        }

        return $this->_listCache[$key];
    }

    /**
     * Returns the number of shares that $userid has access to.
     *
     * @param string $userid     The userid of the user to check access for.
     * @param integer $perm      The level of permissions required.
     * @param mixed $attributes  Restrict the shares counted to those
     *                           matching $attributes. An array of
     *                           attribute/values pairs or a share owner
     *                           username.
     *
     * @return integer  The number of shares
     */
    protected function _countShares($userid, $perm = Horde_Perms::SHOW,
                          $attributes = null)
    {
        $shares = $this->_listShares($userid, array('perm' => $perm, 'attributes' => $attributes));
        return count($shares);
    }

    /**
     * Returns a new share object.
     *
     * @param string $name  The share's name.
     *
     * @return Horde_Share_Object_kolab  A new share object.
     */
    protected function &_newShare($name)
    {
        $storageObject = new Horde_Share_Object_Kolab($name, $this->_type);
        return $storageObject;
    }

    /**
     * Adds a share to the shares system.
     *
     * The share must first be created with Horde_Share_kolab::_newShare(),
     * and have any initial details added to it, before this function is
     * called.
     *
     * @param Horde_Share_Object_kolab $share  The new share object.
     */
    protected function _addShare(&$share)
    {
        return $share->save();
    }

    /**
     * Removes a share from the shares system permanently.
     *
     * @param Horde_Share_Object_Kolab $share  The share to remove.
     */
    protected function _removeShare(Horde_Share_Object $share)
    {
        $share_id = $share->getName();
        $result = $share->delete();
    }

    /**
     * Checks if a share exists in the system.
     *
     * @param string $share  The share to check.
     *
     * @return boolean  True if the share exists.
     */
    protected function _exists($object)
    {
        if (empty($object)) {
            return false;
        }

        /** Get the corresponding folder for this share ID */
        $folder = $this->_list->getByShare($object, $this->_type);
        if ($folder instanceof PEAR_Error) {
            throw new Horde_Share_Exception($folder->getMessage());
        }

        return $folder->exists();
    }

    /**
     * Create a default share for the current app
     *
     * @return string The share ID of the new default share.
     */
    public function getDefaultShare()
    {
        $default = $this->_list->getDefault($this->_type);
        if ($default instanceof PEAR_Error) {
            throw new Horde_Share_Exception($default->getMessage());
        }
        if ($default !== false) {
            return $this->getShare($default->getShareId());
        }

        /** Okay, no default folder yet */
        $share = $this->newShare($GLOBALS['registry']->getAuth(), $GLOBALS['registry']->getAuth());

        /** The value does not matter here as the share will rewrite it */
        $share->set('name', '');
        $result = $this->addShare($share);

        return $share;
    }
}
