<?php
/**
 * The interface describing a Kolab folder.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The interface describing a Kolab folder.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage_Folder
{
    /**
     * Return the storage path of the folder.
     *
     * @return string The storage path of the folder.
     */
    public function getPath();

    /**
     * Returns a readable title for this folder.
     *
     * @return string  The folder title.
     */
    public function getTitle();

    /**
     * Return the namespace type of the folder.
     *
     * @return string The namespace type of the folder.
     */
    public function getNamespace();

    /**
     * Return the namespace prefix of the folder.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The namespace prefix of the folder.
     */
    public function getPrefix();

    /**
     * Returns the owner of the folder.
     *
     * @return string The owner of this folder.
     */
    public function getOwner();

    /**
     * Returns the folder path without namespace components.
     *
     * @return string The subpath of this folder.
     */
    public function getSubpath();

    /**
     * Returns the folder parent.
     *
     * @return string The parent of this folder.
     */
    public function getParent();

    /**
     * Is this a default folder?
     *
     * @return boolean Boolean that indicates the default status.
     */
    public function isDefault();









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
    public function save($attributes = null);

    /**
     * Delete the specified message from this folder.
     *
     * @param  string  $id      IMAP id of the message to be deleted.
     * @param  boolean $trigger Should the folder be triggered?
     *
     * @return NULL
     */
    public function deleteMessage($id, $trigger = true);

    /**
     * Move the specified message to the specified folder.
     *
     * @param string $id     IMAP id of the message to be moved.
     * @param string $folder Name of the receiving folder.
     *
     * @return boolean True if successful.
     */
    public function moveMessage($id, $folder);

    /**
     * Move the specified message to the specified share.
     *
     * @param string $id    IMAP id of the message to be moved.
     * @param string $share Name of the receiving share.
     *
     * @return NULL
     */
    public function moveMessageToShare($id, $share);

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
                               &$old_object = null);

}
