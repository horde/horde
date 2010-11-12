<?php
/**
 * Extension of the Horde_Share_Object class for storing share information in
 * the DataTree driver.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_Share
 */
class Horde_Share_Object_Datatree extends Horde_Share_Object
{
    /**
     * The actual storage object that holds the data.
     *
     * @var mixed
     */
    public $datatreeObject;

    /**
     * Constructor.
     *
     * @param DataTreeObject_Share $datatreeObject  A DataTreeObject_Share
     *                                              instance.
     */
    public function __construct(Horde_Share_Object_Datatree_Share $datatreeObject)
    {
        $this->datatreeObject = $datatreeObject;
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
    public function _set($attribute, $value)
    {
        return $this->datatreeObject->set($attribute, $value);
    }

    /**
     * Returns one of the attributes of the object, or null if it isn't
     * defined.
     *
     * @param string $attribute  The attribute to retrieve.
     *
     * @return mixed  The value of the attribute, or an empty string.
     */
    public function _get($attribute)
    {
        return $this->datatreeObject->get($attribute);
    }

    /**
     * Returns the ID of this share.
     *
     * @return string  The share's ID.
     */
    protected function _getId()
    {
        return $this->datatreeObject->getId();
    }

    /**
     * Returns the name of this share.
     *
     * @return string  The share's name.
     */
    protected function _getName()
    {
        return $this->datatreeObject->getName();
    }

    /**
     * Saves the current attribute values.
     */
    protected function _save()
    {
        return $this->datatreeObject->save();
    }

    /**
     * Checks to see if a user has a given permission.
     *
     * @param string $userid       The userid of the user.
     * @param integer $permission  A Horde_Perms::* constant to test for.
     * @param string $creator      The creator of the event.
     *
     * @return boolean  Whether or not $userid has $permission.
     */
    public function hasPermission($userid, $permission, $creator = null)
    {
        if ($userid && $userid == $this->datatreeObject->get('owner')) {
            return true;
        }

        return $this->getShareOb()->getPermsObject()->hasPermission($this->getPermission(), $userid, $permission, $creator);
    }

    /**
     * Sets the permission of this share.
     *
     * @param Horde_Perms_Permission $perm  Permission object.
     * @param boolean $update         Should the share be saved
     *                                after this operation?
     *
     * @return boolean  True if no error occured, PEAR_Error otherwise
     */
    public function setPermission(&$perm, $update = true)
    {
        $this->datatreeObject->data['perm'] = $perm->getData();
        if ($update) {
            return $this->datatreeObject->save();
        }
        return true;
    }

    /**
     * Returns the permission of this share.
     *
     * @return Horde_Perms_Permission  Permission object that represents the
     *                           permissions on this share
     */
    function getPermission()
    {
        $perm = new Horde_Perms_Permission($this->datatreeObject->getName());
        $perm->data = isset($this->datatreeObject->data['perm'])
            ? $this->datatreeObject->data['perm']
            : array();

        return $perm;
    }

}
