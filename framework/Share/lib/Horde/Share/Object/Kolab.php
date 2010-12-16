<?php
/**
 * Extension of the Horde_Share_Object class for handling Kolab share
 * information.
 *
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
class Horde_Share_Object_Kolab extends Horde_Share_Object implements Serializable
{
    /** Serializable version **/
    const VERSION = 2;

    /**
     * The Kolab folder this share is based on.
     *
     * @var Kolab_Folder
     */
    protected $_folder;

    /**
     * The Kolab folder name.
     *
     * @var string
     */
    protected $_folder_name;

    /**
     * A cache for the share attributes.
     *
     * @var array
     */
    protected $_data;

    /**
     * Our Kolab folder list handler
     *
     * @var Kolab_List
     */
    protected $_list;

    /**
     * Constructor.
     *
     * Sets the folder name.
     *
     * @param string $id  The share id.
     */
    public function __construct($id, $type)
    {
        // We actually ignore the random id string that all horde apps provide
        // as initial name and wait for a set('name', 'xyz') call. But we want
        // to know if we should create a default share.
        if ($id == $GLOBALS['registry']->getAuth()) {
            $this->_data['default'] = true;
        } else {
            $this->_data['default'] = false;
        }
        $this->_type = $type;
        $this->__wakeup();
    }

    /**
     * Associates a Share object with this share.
     *
     * @param Horde_Share $shareOb  The Share object.
     */
    public function setShareOb($shareOb)
    {
        $this->_list = $shareOb->getStorage();
        if (isset($this->_folder_name)) {
            $this->_folder = $this->_list->getFolder($this->_folder_name);
        }
    }

    public function serialize()
    {
        $data = array(
            self::VERSION,
            $this->_data,
            $this->_folder_name,
            $this->_shareCallback
        );
    }

    /**
     * Unserialize object.
     *
     * @param <type> $data
     */
    public function unserialize($data)
    {
        $data = @unserialize($data);
        if (!is_array($data) ||
            !isset($data[0]) ||
            ($data[0] != self::VERSION)) {
            throw new Exception('Cache version change');
        }

        $this->_data = $data[1];
        $this->_folder_name = $data[2];
        if (empty($data[3])) {
            throw new Exception('Missing callback for unserializing Horde_Share_Object');
        }
        $this->_shareCallback = $data[3];
        $this->setShareOb(call_user_func($this->_shareCallback));
    }

    /**
     * Returns the default share name for the current application.
     *
     * @return string  The default share name.
     */
    public function getDefaultShareName()
    {
        switch ($this->_type) {
        case 'contact':
            return Horde_Share_Translation::t("Contacts");
        case 'note':
            return Horde_Share_Translation::t("Notes");
        case 'event':
            return Horde_Share_Translation::t("Calendar");
        case 'task':
            return Horde_Share_Translation::t("Tasks");
        case 'filter':
            return Horde_Share_Translation::t("Filters");
        case 'h-prefs':
            return Horde_Share_Translation::t("Preferences");
        }
    }

    /**
     * Sets the folder for this storage object.
     *
     * @param string $folder  Name of the Kolab folder.
     * @param array  $perms  The permissions of the folder if they are known.
     */
    public function setFolder($folder)
    {
        if (!isset($this->_folder)) {
            $this->_folder = $folder;
            $this->_folder_name = $folder->name;
        } else {
           throw new Horde_Share_Exception(Horde_Share_Translation::t("The share has already been initialized!"));
        }
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    public function getId()
    {
        return $this->_folder->getShareId();
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    public function getName()
    {
        return $this->_folder->getShareId();
    }

    /**
     * Returns an attribute value from this object.
     *
     * @param string $attribute  The attribute to return.
     *
     * @return mixed  The value for $attribute.
     */
    public function get($attribute)
    {
        if (isset($this->_data[$attribute])) {
            return $this->_data[$attribute];
        }

        if (!isset($this->_folder)) {
            return $this->_folderError();
        }

        switch ($attribute) {
        case 'owner':
            $this->_data['owner'] = $this->_folder->getOwner();
            break;

        case 'name':
            $this->_data['name'] = $this->_folder->getTitle();
            break;

        case 'type':
            $this->_data['type'] = $this->_folder->getType();
            break;

        case 'params':
            $params = @unserialize($this->_folder->getAttribute('params'));
            $default = array('source' => 'kolab',
                             'default' => $this->get('default'),
                             'name' => $this->get('name'));
            $type = $this->get('type');
            if (!($type instanceof PEAR_Error) && $type == 'event') {
                $default = array_merge($default, array(
                                           'fbrelevance' => $this->_folder->getFbrelevance(),
                                           'xfbaccess'   => $this->_folder->getXfbaccess()
                                       ));
            }
            if ($params instanceof PEAR_Error || $params == '') {
                $params = $default;
            }
            $this->_data['params'] = serialize(array_merge($default, $params));
            break;

        case 'default':
            $this->_data['default'] = $this->_folder->isDefault();
            break;

        default:
            $annotation = $this->_folder->getAttribute($attribute);
            if ($annotation instanceof PEAR_Error || empty($annotation)) {
                $annotation = '';
            }
            $this->_data[$attribute] = $annotation;
            break;
        }

        return $this->_data[$attribute];
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return mixed  True if setting the attribute did succeed, a PEAR_Error
     *                otherwise.
     */
    public function set($attribute, $value)
    {
        switch ($attribute) {
        case 'name':
            /* On folder creation of default shares we wish to ignore
             * the names provided by the Horde applications. We use
             * the Kolab default names. */
            if (!isset($this->_folder)) {
                if ($this->get('default')) {
                    $value = $this->getDefaultShareName();
                }
                $this->setFolder($this->_list->getNewFolder());
            }
            $this->_folder->setName($value);
            $this->_data['name'] = $this->_folder->getTitle();
            break;

        case 'params':
            $value = unserialize($value);
            if (isset($value['default'])) {
                $this->_data['default'] = $value['default'];
                unset($value['default']);
            }
            $value = serialize($value);

        default:
            $this->_data[$attribute] = $value;
        }
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        if (!isset($this->_folder)) {
            return $this->_folderError();
        }

        $data = $this->_data;
        /** The name is handled immediately when set */
        unset($data['name']);
        $data['type'] = $this->_type;

        $result = $this->_folder->save($data);
        if ($result instanceof PEAR_Error) {
            return $result;
        }

        /** The name may have changed */
        $this->_data['name'] = $this->_folder->getTitle();
        $this->_folder_name = $this->_folder->name;
        return true;
    }

    /**
     * Delete this share.
     *
     * @return boolean|PEAR_Error  True on success.
     */
    public function delete()
    {
        if (!isset($this->_folder)) {
            return $this->_folderError();
        }
        return $this->_folder->delete();
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the shared object.
     *
     * @return boolean|PEAR_Error  Whether or not $userid has $permission.
     */
    public function hasPermission($userid, $permission, $creator = null)
    {
        if (!isset($this->_folder)) {
            return $this->_folderError();
        }
        return $this->_folder->hasPermission($userid, $permission, $creator);
    }

    /**
     * Returns the permissions from this storage object.
     *
     * @return Horde_Perms_Permission_Kolab|PEAR_Error  The permissions on the share.
     */
    public function getPermission()
    {
        if (!isset($this->_folder)) {
            return $this->_folderError();
        }
        return $this->_folder->getPermission();
    }

    /**
     * Sets the permissions on the share.
     *
     * @param Horde_Perms_Permission_Kolab $perms Permission object to folder on the
     *                                     object.
     * @param boolean $update              Save the updated information?
     *
     * @return boolean|PEAR_Error  True on success.
     */
    public function setPermission($perms, $update = true)
    {
        if (!isset($this->_folder)) {
            return $this->_folderError();
        }
        return $this->_folder->setPermission($perms, $update);
    }

    /**
     * Return a standard error in case the share has not been
     * correctly initialized.
     *
     * @throws Horde_Share_Exception
     */
    protected function _folderError()
    {
        throw new Horde_Share_Exception(Horde_Share_Translation::t("The Kolab share object has not been initialized yet!"));
    }
}
