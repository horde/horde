<?php
/**
 * A library for accessing a Kolab storage (usually IMAP).
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The Horde_Kolab_Storage class provides the means to access the
 * Kolab server storage for groupware objects.
 *
 * To get access to the folder handling you would do the following:
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getFolder('INBOX/Calendar');
 *   </code>
 *
 *  or (in case you are dealing with share identifications):
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getShare(Auth::getAuth(), 'event');
 *   </code>
 *
 * To access data in a share (or folder) you need to retrieve the
 * corresponding data object:
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getShareData(Auth::getAuth(), 'event');
 *   </code>
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage
{
    /**
     * The master Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_master;

    /**
     * An array of Horde_Kolab_Storage_Driver connections to Kolab
     * storage systems.
     *
     * @var array
     */
    protected $connections = array();

    /**
     * The parameters for the base connection.
     *
     * @var array
     */
    private $_params;

    /**
     * A connection to the cache object.
     *
     * @var Horde_Cache
     */
    private $_cache;

    /**
     * The list of existing folders on this server.
     *
     * @var array
     */
    private $_list;

    /**
     * A cache for folder objects (these do not necessarily exist).
     *
     * @var array
     */
    private $_folders;

    /**
     * A cache array listing a default folder for each folder type.
     *
     * @var array
     */
    private $_defaults;

    /**
     * A cache array listing a the folders for each folder type.
     *
     * @var array
     */
    private $_types;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $master The primary connection driver.
     * @param string $driver The driver used for the primary storage connection.
     * @param array  $params Additional connection parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Driver $master,
        Horde_Cache $cache,
        $params = array()
    ) {
        $this->_master = $master;
        $this->_cache  = new Horde_Kolab_Storage_Cache($cache);

        $this->_params = $params;

        if (isset($this->_params['owner'])) {
            $this->_owner = $this->_params['owner'];
        } else if (class_exists('Horde_Auth')) {
            $this->_owner = $GLOBALS['registry']->getAuth();
        } else {
            $this->_owner = '';
        }

        $this->__wakeup();
    }

    /**
     * Factory.
     *
     * @param string $driver The driver used for the primary storage connection.
     * @param array  $params Additional connection parameters.
     *
     * @return Horde_Kolab_Storage_List A concrete list instance.
     */
    static public function &factory($driver, $params = array())
    {
        if (!empty($GLOBALS['conf']['kolab']['storage']['cache']['folders'])) {
            $signature = hash('md5', serialize(array($driver, $params))) . '|list';

            $this->_cache = $GLOBALS['injector']->getInstance('Horde_Cache');

            $data = $this->_cache->get($signature,
                                       $GLOBALS['conf']['kolab']['storage']['cache']['folders']['lifetime']);
            if ($data) {
                $list = @unserialize($data);;
                if ($list instanceOf Horde_Kolab_Storage) {
                    register_shutdown_function(array($list, 'shutdown'));
                    return $list;
                }
            }
        }
        $list = new Horde_Kolab_Storage($driver, $params);
        if (!empty($GLOBALS['conf']['kolab']['storage']['cache']['folders'])) {
            register_shutdown_function(array($list, 'shutdown'));
        }
        return $list;
    }

    /**
     * Clean the simulated IMAP store.
     *
     * @return NULL
     */
    public function clean()
    {
        $this->_list     = null;
        $this->_folders  = null;
        $this->_defaults = null;
        $this->_types    = null;
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['connections']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Initializes the object.
     *
     * @return NULL
     */
    public function __wakeup()
    {
        if (!isset($this->_folders)) {
            $this->_folders = array();
        }

        foreach ($this->_folders as $key => $folder) {
            $result = $this->getConnection($key);
            $folder->restore($this, $result->connection);
        }
    }

    /**
     * Stores the object in the session cache.
     *
     * @return NULL
     */
    protected function shutdown()
    {
        $data = @serialize($this);
        $this->_cache->set($signature, $data,
                           $GLOBALS['conf']['kolab']['storage']['cache']['folders']['lifetime']);
    }

    /**
     * Return the data cache associated with this storage instance.
     *
     * @return Horde_Kolab_Storage_Cache The cache object
     */
    public function getDataCache()
    {
        return $this->_cache;
    }

    /**
     * Return the connection driver and the folder name for the given key.
     *
     * @param string $key The key specifying a connection (may be a folder name)
     *
     * @return stdClass An object with the parameter "connection" set to the
     *                  connection identified by the given key and the parameter
     *                  "name" set to the folder name if the given key contained
     *                  a folder name.
     */
    public function &getConnection($key = null)
    {
        $result = new stdClass;
        if (strpos('@', $key)) {
            list($connection, $result->name) = explode('@', $folder, 2);
        } else {
            $connection   = null;
            $result->name = $key;
        }

        if (empty($connection) || !isset($this->connections[$connection])) {
            $result->connection = $this->_master;
        } else {
            $result->connection = $this->connections[$connection];
        }
        return $result;
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of IMAP folders, represented as
     *               Horde_Kolab_Storage_Folder objects.
     */
    public function &listFolders()
    {
        $this->_initiateCache();
        $result = array_keys($this->_list);
        return $result;
    }

    /**
     * Get several or all Folder objects.
     *
     * @param array $folders Several folder names or unset to retrieve
     *                       all folders.
     *
     * @return array An array of Horde_Kolab_Storage_Folder objects.
     */
    public function getFolders($folders = null)
    {
        if (!isset($folders)) {
            $folders = $this->listFolders();
        }

        $result = array();
        foreach ($folders as $folder) {
            $result[] = $this->getFolder($folder);
        }
        return $result;
    }

    /**
     * Get a Folder object.
     *
     * @param string $folder The folder name.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function &getFolder($folder)
    {
        if (!isset($this->_folders[$folder])) {
            $result = $this->getConnection($folder);

            $kf = new Horde_Kolab_Storage_Folder_Base($result->name);
            $kf->restore($this, $result->connection);
            $this->_folders[$folder] = &$kf;
        }
        return $this->_folders[$folder];
    }

    /**
     * Get a new Folder object.
     *
     * @param string $connection The name of the connection for the folder.
     *
     * @return Horde_Kolab_Storage_Folder The new Kolab folder object.
     */
    public function getNewFolder($connection = null)
    {
        if (empty($connection) || !isset($this->connections[$connection])) {
            $connection = &$this->_master;
        } else {
            $connection = &$this->connections[$connection];
        }
        $folder = new Horde_Kolab_Storage_Folder_Base(null);
        $folder->restore($this, $connection);
        return $folder;
    }

    /**
     * Get a Folder object based on a share ID.
     *
     * @param string $share The share ID.
     * @param string $type  The type of the share/folder.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function getByShare($share, $type)
    {
        $folder = $this->_parseShare($share, $type);
        return $this->getFolder($folder);
    }

    /**
     * Get a list of folders based on the type.
     *
     * @param string $type The type of the share/folder.
     *
     * @return Horde_Kolab_Storage_Folder The list of Kolab folder objects.
     */
    public function getByType($type)
    {
        $this->_initiateCache();
        if (isset($this->_types[$type])) {
            return $this->getFolders($this->_types[$type]);
        } else {
            return array();
        }
    }

    /**
     * Get the default folder for a certain type.
     *
     * @param string $type The type of the share/folder.
     *
     * @return mixed The default folder, false if there is no default.
     */
    public function getDefault($type)
    {
        $this->_initiateCache();
        if (isset($this->_defaults[$this->_owner][$type])) {
            return $this->getFolder($this->_defaults[$this->_owner][$type]);
        } else {
            return false;
        }
    }

    /**
     * Get the default folder for a certain type from a different owner.
     *
     * @param string $owner The folder owner.
     * @param string $type  The type of the share/folder.
     *
     * @return mixed The default folder, false if there is no default.
     */
    public function getForeignDefault($owner, $type)
    {
        $this->_initiateCache();
        if (isset($this->_defaults[$owner][$type])) {
            return $this->getFolder($this->_defaults[$owner][$type]);
        } else {
            return false;
        }
    }

    /**
     * Converts the horde syntax for shares to storage identifiers.
     *
     * @param string $share The share ID that should be parsed.
     * @param string $type  The type of the share/folder.
     *
     * @return string The corrected folder name.
     */
    private function _parseShare($share, $type)
    {
        // Handle default shares
        if (class_exists('Horde_Auth')
            && $share == $GLOBALS['registry']->getAuth()) {
            $result = $this->getDefault($type);
            if (!empty($result)) {
                return $result->name;
            }
        }
        return rawurldecode($share);
    }

    /**
     * Start the cache for the type specific and the default folders.
     *
     * @return NULL
     */
    private function _initiateCache()
    {
        if (isset($this->_list) && isset($this->_types) && isset($this->_defaults)) {
            return;
        }

        $this->_list     = array();
        $this->_types    = array();
        $this->_defaults = array();

        $folders = array_merge($this->_list, $this->_master->getMailboxes());
        foreach ($this->connections as $key => $connection) {
            $list = $connection->getMailboxes();
            foreach ($list as $item) {
                $folders[] = $key . '@' . $item;
            }
        }

        foreach ($folders as $folder) {
            $fo      = $this->getFolder($folder);
            $type    = $fo->getType();
            $default = $fo->isDefault();
            $owner   = $fo->getOwner();

            $this->_list[$folder] = array($type, $default, $owner);
            if (!isset($this->_types[$type])) {
                $this->_types[$type] = array();
            }
            $this->_types[$type][] = $folder;
            if ($default) {
                $this->_defaults[$owner][$type] = $folder;
            }
        }
    }

    /**
     * Update the cache variables.
     *
     * @param Horde_Kolab_Storage_Folder &$folder The folder that was added.
     *
     * @return NULL
     */
    public function addToCache($folder)
    {
        $this->_initiateCache();

        try {
            $type    = $folder->getType();
            $default = $folder->isDefault();
            $owner   = $folder->getOwner();
        } catch (Exception $e) {
            Horde::logMessage(sprintf("Error while updating the Kolab folder list cache: %s.",
                                      $e->getMessage()), 'ERR');
            return;
        }

        $this->_folders[$folder->name] = &$folder;
        $this->_list[$folder->name]    = array($type, $default, $owner);
        $this->_types[$type][]         = $folder->name;

        if ($default) {
            $this->_defaults[$owner][$type] = $folder->name;
        }
    }

    /**
     * Update the cache variables.
     *
     * @param Horde_Kolab_Storage_Folder &$folder The folder that was removed.
     *
     * @return NULL
     */
    public function removeFromCache($folder)
    {
        $this->_initiateCache();

        unset($this->_folders[$folder->name]);
        if (isset($this->_list)) {
            if (in_array($folder->name, array_keys($this->_list))) {
                list($type, $default, $owner) = $this->_list[$folder->name];
                unset($this->_list[$folder->name]);
            }
        }
        if (isset($this->_types[$type])) {
            $idx = array_search($folder->name, $this->_types[$type]);
            if ($idx !== false) {
                unset($this->_types[$type][$idx]);
            }
        }
        if ($default && isset($this->_defaults[$owner][$type])) {
            unset($this->_defaults[$owner][$type]);
        }
    }

    /**
     * Return the folder object corresponding to the share of the
     * specified type (e.g. "contact", "event" etc.).
     *
     * @param string $share The id of the share.
     * @param string $type  The share type.
     *
     * @return Horde_Kolab_Folder The folder object representing
     *                            the share.
     */
    public function getShare($share, $type)
    {
        $share = $this->getByShare($share, $type);
        return $share;
    }

    /**
     * Return a data object for accessing data in the specified
     * folder.
     *
     * @param Horde_Kolab_Storage_Folder &$folder     The folder object.
     * @param string                     $data_type   The type of data we want
     *                                                to access in the folder.
     * @param int                        $data_format The version of the data
     *                                                format we want to access
     *                                                in the folder.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData(Horde_Kolab_Storage_Folder $folder,
                             $data_type = null, $data_format = 1)
    {
        if (empty($data_type)) {
            $data_type = $folder->getType();
        }
        $data = $folder->getData($data_type, $data_format);
        return $data;
    }

    /**
     * Return a data object for accessing data in the specified
     * share.
     *
     * @param string $share       The id of the share.
     * @param string $type        The share type.
     * @param string $data_type   The type of data we want to
     *                            access in the folder.
     * @param int    $data_format The version of the data format
     *                            we want to access in the folder.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getShareData($share, $type, $data_type = null, $data_format = 1)
    {
        $folder = $this->getShare($share, $type);
        $data   = $this->getData($folder, $data_type, $data_format);
        return $data;
    }

    /**
     * Return a data object for accessing data in the specified
     * folder.
     *
     * @param string $folder      The name of the folder.
     * @param string $data_type   The type of data we want to
     *                            access in the folder.
     * @param int    $data_format The version of the data format
     *                            we want to access in the folder.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getFolderData($folder, $data_type = null, $data_format = 1)
    {
        $folder = $this->getFolder($folder);
        $data   = $this->getData($folder, $data_type, $data_format);
        return $data;
    }
}

