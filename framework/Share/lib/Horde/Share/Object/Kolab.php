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
    /**
     * Serializable version.
     */
    const VERSION = 2;

    /**
     * The share id.
     *
     * @var string
     */
    private $_id;

    /**
     * The share attributes.
     *
     * @var array
     */
    protected $_data;

    /**
     * Constructor.
     *
     * @param string $id    The share id.
     * @param array  $data  The share data.
     */
    public function __construct($id, array $data = array())
    {
        $this->_id   = $id;
        $this->_data = $data;
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array(
            self::VERSION,
            $this->_id,
            $this->_data,
            $this->_shareCallback
        ));
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

        $this->_id = $data[1];
        $this->_data = $data[2];
        if (empty($data[3])) {
            throw new Exception('Missing callback for unserializing Horde_Share_Object');
        }
        $this->_shareCallback = $data[3];
        $this->setShareOb(call_user_func($this->_shareCallback));
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    public function getName()
    {
        return $this->_id;
    }

    /**
     * Returns an attribute value from this object.
     *
     * @param string $attribute  The attribute to return.
     *
     * @return mixed The value for $attribute.
     */
    public function get($attribute)
    {
        if (isset($this->_data[$attribute])) {
            return $this->_data[$attribute];
        }
    }

    /**
     * Sets an attribute value in this object.
     *
     * @param string $attribute  The attribute to set.
     * @param mixed $value       The value for $attribute.
     *
     * @return NULL
     */
    public function set($attribute, $value)
    {
        $this->_data[$attribute] = $value;
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        $this->_id = $this->getShareOb()->save($this->_id, $this->_data);
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
}
