<?php
/**
 * Defines the Kolab data handler.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Defines the Kolab data handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage_Data
extends Horde_Kolab_Storage_Queriable
{
    /** Identifies the preferences query */
    /** @since Horde_Kolab_Storage 1.1.0 */
    const QUERY_PREFS  = 'Preferences';
    const QUERY_HISTORY  = 'History';

    /**
     * Return the folder path for this data handler.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The folder path.
     */
    public function getPath();

    /**
     * Return the ID of the current user.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return string The current user.
     */
    public function getAuth();

    /**
     * Return the ID of this data handler.
     *
     * @return string The ID.
     */
    public function getId();

    /**
     * Return the ID parameters for this data handler.
     *
     * @return array The ID parameters.
     */
    public function getIdParameters();

    /**
     * Return the data type represented by this object.
     *
     * @return string The type of data this instance handles.
     */
    public function getType();

    /**
     * Return the data version.
     *
     * @return string The data version.
     */
    public function getVersion();

    /**
     * Report the status of this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp The stamp that can be used for
     *                                          detecting folder changes.
     */
    public function getStamp();

    /**
     * Create a new object.
     *
     * @param array   &$object The array that holds the object data.
     * @param boolean $raw     True if the data to be stored has been provided in
     *                         raw format.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function create(&$object, $raw = false);

    /**
     * Modify an existing object.
     *
     * @param array   $object The array that holds the updated object data.
     * @param boolean $raw    True if the data to be stored has been provided in
     *                        raw format.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case an error occured while
     *                                       saving the data.
     */
    public function modify($object, $raw = false);

    /**
     * Retrieves the objects for the given UIDs.
     *
     * @param array $uids The message UIDs.
     *
     * @return array An array of objects.
     */
    public function fetch($uids);

    /**
     * Retrieves the complete message for the given UID.
     *
     * @param string $uid The message UID.
     *
     * @return array The message encapsuled as an array that contains a
     *               Horde_Mime_Headers and a Horde_Mime_Part object.
     */
    public function fetchComplete($uid);

    /**
     * Return the backend ID for the given object ID.
     *
     * @param string $object_uid The object ID.
     *
     * @return string The backend ID for the object.
     */
    public function getBackendId($object_id);

    /**
     * Check if the given object ID exists.
     *
     * @param string $object_id The object ID.
     *
     * @return boolean True if the ID was found, false otherwise.
     */
    public function objectIdExists($object_id);

    /**
     * Return the specified object.
     *
     * @param string $object_id The object id.
     *
     * @return array The object data as an array.
     */
    public function getObject($object_id);

    /**
     * Return the specified attachment.
     *
     * @param string $attachment_id The attachment id.
     *
     * @return resource An open stream to the attachment data.
     */
    public function getAttachment($attachment_id);

    /**
     * Retrieve all object ids in the current folder.
     *
     * @return array The object ids.
     */
    public function getObjectIds();

    /**
     * Retrieve all objects in the current folder.
     *
     * @return array An array of all objects.
     */
    public function getObjects();

    /**
     * Retrieve all objects in the current folder by backend id.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array An array of all objects.
     */
    public function getObjectsByBackendId();

    /**
     * Retrieve an object in the current folder by backend id.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @param string $uid Backend id of the object to be returned.
     *
     * @return array An array of all objects.
     */
    public function getObjectByBackendId($uid);

    /**
     * Return the mapping of object IDs to backend IDs.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The object to backend mapping.
     */
    public function getObjectToBackend();

    /**
     * Retrieve the list of object duplicates.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The list of duplicates.
     */
    public function getDuplicates();

    /**
     * Retrieve the list of object errors.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @return array The list of errors.
     */
    public function getErrors();

    /**
     * Move the specified message from the current folder into a new
     * folder.
     *
     * @param string $object_id  ID of the message to be moved.
     * @param string $new_folder Target folder.
     *
     * @return NULL
     */
    public function move($object_id, $new_folder);

    /**
     * Delete the specified objects from this data set.
     *
     * @param array|string $object_ids Id(s) of the object to be deleted.
     *
     * @return NULL
     */
    public function delete($object_ids);

    /**
     * Delete all objects from this data set.
     *
     * @return NULL
     */
    public function deleteAll();

    /**
     * Delete the specified messages from this folder.
     *
     * @since Horde_Kolab_Storage 1.1.0
     *
     * @param array|string $uids Backend id(s) of the message to be deleted.
     *
     * @return NULL
     */
    public function deleteBackendIds($uids);
}