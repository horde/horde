<?php
/**
 * The basic decorator definition for Kolab folders.
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
 * The basic decorator definition for Kolab folders.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Decorator_Base
implements Horde_Kolab_Storage_Folder
{

    /**
     * The decorated folder.
     *
     * @var Horde_Kolab_Storage_Folder
     */
    protected $_folder;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Folder $folder The folder to be decorated.
     */
    public function __construct(Horde_Kolab_Storage_Folder $folder)
    {
        $this->_folder = $folder;
    }

    /**
     * Return the storage path of the folder.
     *
     * @return string The storage path of the folder.
     */
    public function getPath()
    {
        return $this->_folder->getPath();
    }

    /**
     * Returns a readable title for this folder.
     *
     * @return string  The folder title.
     */
    public function getTitle()
    {
        return $this->_folder->getTitle();
    }

    /**
     * Return the namespace of the folder.
     *
     * @return string The namespace of the folder.
     */
    public function getNamespace()
    {
        return $this->_folder->getNamespace();
    }

    /**
     * Returns the owner of the folder.
     *
     * @return string The owner of this folder.
     */
    public function getOwner()
    {
        return $this->_folder->getOwner();
    }

    /**
     * Returns the folder path without namespace components.
     *
     * @return string The subpath of this folder.
     */
    public function getSubpath()
    {
        return $this->_folder->getSubpath();
    }

    /**
     * Returns the folder parent.
     *
     * @return string The parent of this folder.
     */
    public function getParent()
    {
        return $this->_folder->getParent();
    }

    /**
     * Is this a default folder?
     *
     * @return boolean Boolean that indicates the default status.
     */
    public function isDefault()
    {
        return $this->_folder->isDefault();
    }



    /**
     * Set a new storage path for the folder. The new path will be
     * realized when saving the folder.
     *
     * @param string $path  The new folder path.
     */
    public function setPath($path)
    {
        $this->_folder->setPath($path);
    }

    /**
     * Set a new title for the folder. The new title will be realized when
     * saving the folder.
     *
     * @param string $title     The new folder title.
     * @param string $namespace The new namespace of the folder.
     *
     * @return NULL
     */
    public function setTitle($title)
    {
        $this->_folder->setTitle($title);
    }

    /**
     * Set a new title for the folder and assign it to the namespace of other
     * users. The new title will be realized when saving the folder.
     *
     * @param string $title The new folder title.
     * @param string $owner The new owner of the folder.
     *
     * @return NULL
     */
    public function setTitleInOther($title, $owner)
    {
        $this->_folder->setTitleInOther($title, $owner);
    }

    /**
     * Set a new title for the folder and assign it to the shared namespace. The
     * new title will be realized when saving the folder.
     *
     * @param string $title The new folder title.
     *
     * @return NULL
     */
    public function setTitleInShared($title)
    {
        $this->_folder->setTitleInShared($title);
    }

    /**
     * Retrieve the driver for this folder.
     *
     * @return Horde_Kolab_Storage_Driver The folder driver.
     */
    public function getDriver()
    {
        return $this->_folder->getDriver();
    }

    /**
     * Get the permissions for this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Permission The permission handler.
     */
    public function getPermission()
    {
        return $this->_folder->getPermission();
    }

    /**
     * Sets the permissions on this folder.
     *
     * @param Horde_Kolab_Storage_Folder_Permission $perms  Permission object.
     * @param boolean                               $update Save the updated
     *                                                      information?
     *
     * @return NULL
     */
    public function setPermission(
        Horde_Kolab_Storage_Folder_Permission $perms,
        $update = true
    ) {
        $this->_folder->setPermission($perms, $update);
    }

    /**
     * Saves the folder.
     *
     * @param array $attributes An array of folder attributes. You can
     *                          set any attribute but there are a few
     *                          special ones like 'type', 'default',
     *                          'owner' and 'desc'.
     *
     * @return NULL
     */
    public function save($attributes = null)
    {
        $this->_folder->save($attributes);
    }

    /**
     * Delete the specified message from this folder.
     *
     * @param  string  $id      IMAP id of the message to be deleted.
     * @param  boolean $trigger Should the folder be triggered?
     *
     * @return NULL
     */
    public function deleteMessage($id, $trigger = true)
    {
        $this->_folder->deleteMessage($id, $trigger);
    }

    /**
     * Move the specified message to the specified folder.
     *
     * @param string $id     IMAP id of the message to be moved.
     * @param string $folder Name of the receiving folder.
     *
     * @return boolean True if successful.
     */
    public function moveMessage($id, $folder)
    {
        $this->_folder->moveMessage($id, $folder);
    }

    /**
     * Move the specified message to the specified share.
     *
     * @param string $id    IMAP id of the message to be moved.
     * @param string $share Name of the receiving share.
     *
     * @return NULL
     */
    public function moveMessageToShare($id, $share)
    {
        $this->_folder->moveMessageToShare($id, $share);
    }

    /**
     * Save an object in this folder.
     *
     * @param array  $object       The array that holds the data of the object.
     * @param int    $data_version The format handler version.
     * @param string $object_type  The type of the kolab object.
     * @param string $id           The IMAP id of the old object if it
     *                             existed before
     * @param array  $old_object   The array that holds the current data of the
     *                             object.
     *
     * @return boolean True on success.
     */
    public function saveObject(&$object, $data_version, $object_type, $id = null,
                               &$old_object = null)
    {
        $this->_folder->saveObject($object, $data_version, $object_type, $id, $old_object = null);
    }

    /**
     * Return the IMAP ACL of this folder.
     *
     * @return array An array with IMAP ACL.
     */
    public function getAcl()
    {
        return $this->_folder->getAcl();
    }

    /**
     * Set the ACL of this folder.
     *
     * @param $user The user for whom the ACL should be set.
     * @param $acl  The new ACL value.
     *
     * @return NULL
     */
    public function setAcl($user, $acl)
    {
        $this->_folder->setAcl($user, $acl);
    }

    /**
     * Delete the ACL for a user on this folder.
     *
     * @param $user The user for whom the ACL should be deleted.
     *
     * @return NULL
     */
    public function deleteAcl($user)
    {
        $this->_folder->deleteAcl($user);
    }

}
