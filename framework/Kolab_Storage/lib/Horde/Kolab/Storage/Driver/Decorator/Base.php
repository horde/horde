<?php
/**
 * The basic driver decorator definition for accessing Kolab storage.
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
 * The basic driver decorator definition for accessing Kolab storage.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Decorator_Base
implements Horde_Kolab_Storage_Driver
{
    /**
     * The decorated driver.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The decorated driver.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver)
    {
        $this->_driver = $driver;
    }

    /**
     * Return the class name of the decorated driver.
     *
     * @return string The class name of the decorated driver.
     */
    public function getDriverName()
    {
        return get_class($this->_driver);
    }

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        return $this->_driver->createBackend();
    }

    /**
     * Returns the actual backend driver.
     *
     * If there is no driver set the driver should be constructed within this
     * method.
     *
     * @return mixed The backend driver.
     */
    public function getBackend()
    {
        return $this->_driver->getBackend();
    }

    /**
     * Set the backend driver.
     *
     * @param mixed $backend The driver that should be used.
     *
     * @return NULL
     */
    public function setBackend($backend)
    {
        $this->_driver->setBackend($backend);
    }

    /**
     * Returns the parser for data objects.
     *
     * @return Horde_Kolab_Storage_Data_Parser The parser.
     */
    public function getParser()
    {
        return $this->_driver->getParser();
    }

    /**
     * Set the data parser.
     *
     * @param mixed $parser The parser that should be used.
     *
     * @return NULL
     */
    public function setParser(Horde_Kolab_Storage_Data_Parser $parser)
    {
        $this->_driver->setParser($parser);
    }

    /**
     * Return the id of the user currently authenticated.
     *
     * @return string The id of the user that opened the connection.
     */
    public function getAuth()
    {
        return $this->_driver->getAuth();
    }

    /**
     * Return the unique connection id.
     *
     * @return string The connection id.
     */
    public function getId()
    {
        return $this->_driver->getId();
    }

    /**
     * Return the connection parameters.
     *
     * @return array The connection parameters.
     */
    public function getParameters()
    {
        return $this->_driver->getParameters();
    }

    /**
     * Checks if the backend supports CATENATE.
     *
     * @return boolean True if the backend supports CATENATE.
     */
    public function hasCatenateSupport()
    {
        return $this->_driver->hasCatenateSupport();
    }

    /**
     * Retrieves a list of mailboxes from the server.
     *
     * @return array The list of mailboxes.
     */
    public function listFolders()
    {
        return $this->_driver->listFolders();
    }

    /**
     * Create the specified folder.
     *
     * @param string $folder The folder to create.
     *
     * @return NULL
     */
    public function create($folder)
    {
        $this->_driver->create($folder);
    }

    /**
     * Delete the specified folder.
     *
     * @param string $folder  The folder to delete.
     *
     * @return NULL
     */
    public function delete($folder)
    {
        $this->_driver->delete($folder);
    }

    /**
     * Rename the specified folder.
     *
     * @param string $old  The folder to rename.
     * @param string $new  The new name of the folder.
     *
     * @return NULL
     */
    public function rename($old, $new)
    {
        return $this->_driver->listFolders();
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        return $this->_driver->hasAclSupport();
    }

    /**
     * Retrieve the access rights for a folder.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @return array An array of rights.
     */
    public function getAcl($folder)
    {
        return $this->_driver->getAcl($folder);
    }

    /**
     * Retrieve the access rights the current user has on a folder.
     *
     * @param string $folder The folder to retrieve the user ACL for.
     *
     * @return string The user rights.
     */
    public function getMyAcl($folder)
    {
        return $this->_driver->getMyAcl($folder);
    }

    /**
     * Set the access rights for a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to set the ACL for.
     * @param string $acl     The ACL.
     *
     * @return NULL
     */
    public function setAcl($folder, $user, $acl)
    {
        $this->_driver->setAcl($folder, $user, $acl);
    }

    /**
     * Delete the access rights for user on a folder.
     *
     * @param string $folder  The folder to act upon.
     * @param string $user    The user to delete the ACL for
     *
     * @return NULL
     */
    public function deleteAcl($folder, $user)
    {
        $this->_driver->deleteAcl($folder, $user);
    }

    /**
     * Retrieves the specified annotation for the complete list of mailboxes.
     *
     * @param string $annotation The name of the annotation to retrieve.
     *
     * @return array An associative array combining the folder names as key with
     *               the corresponding annotation value.
     */
    public function listAnnotation($annotation)
    {
        return $this->_driver->listAnnotation($annotation);
    }

    /**
     * Fetches the annotation on a folder.
     *
     * @param string $entry  The entry to fetch.
     * @param string $folder The name of the folder.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($entry, $folder)
    {
        return $this->_driver->getAnnotation($entry, $folder);
    }

    /**
     * Sets the annotation on a folder.
     *
     * @param string $folder     The name of the folder.
     * @param string $annotation The annotation to set.
     * @param array  $value      The values to set
     *
     * @return NULL
     */
    public function setAnnotation($folder, $annotation, $value)
    {
        $this->_driver->setAnnotation($folder, $annotation, $value);
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        return $this->_driver->getNamespace();
    }

    /**
     * Returns a stamp for the current folder status. This stamp can be used to
     * identify changes in the folder data.
     *
     * @param string $folder Return the stamp for this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Stamp A stamp indicating the current
     *                                          folder status.
     */
    public function getStamp($folder)
    {
        return $this->_driver->getStamp($folder);
    }

    /**
     * Returns the status of the current folder.
     *
     * @param string $folder Check the status of this folder.
     *
     * @return array An array that contains 'uidvalidity' and 'uidnext'.
     */
    public function status($folder)
    {
        return $this->_driver->status($folder);
    }

    /**
     * Returns the message ids of the messages in this folder.
     *
     * @param string $folder Check the status of this folder.
     *
     * @return array The message ids.
     */
    public function getUids($folder)
    {
        return $this->_driver->getUids($folder);
    }

    /**
     * Fetches the objects for the specified UIDs.
     *
     * @param string $folder The folder to access.
     *
     * @return array The parsed objects.
     */
    public function fetch($folder, $uids, $options = array())
    {
        return $this->_driver->fetch($folder, $uids, $options);
    }

    /**
     * Retrieves the messages for the given message ids.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uids   The message UIDs.
     *
     * @return array An array of message structures parsed into Horde_Mime_Part
     *               instances.
     */
    public function fetchStructure($folder, $uids)
    {
        return $this->_driver->fetchStructure($folder, $uids);
    }

    /**
     * Retrieves a bodypart for the given message ID and mime part ID.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uid    The message UID.
     * @param array  $id     The mime part ID.
     *
     * @return resource|string The body part, as a stream resource or string.
     */
    public function fetchBodypart($folder, $uid, $id)
    {
        return $this->_driver->fetchBodypart($folder, $uid, $id);
    }

    /**
     * Retrieves a complete message.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uid    The message UID.
     *
     * @return array The message encapsuled as an array that contains a
     *               Horde_Mime_Headers and a Horde_Mime_Part object.
     */
    public function fetchComplete($folder, $uid)
    {
        return $this->_driver->fetchComplete($folder, $uid);
    }

    /**
     * Appends a message to the given folder.
     *
     * @param string   $folder  The folder to append the message(s) to.
     * @param resource $msg     The message to append.
     *
     * @return mixed True or the UID of the new message in case the backend
     *               supports UIDPLUS.
     */
    public function appendMessage($folder, $msg)
    {
        return $this->_driver->appendMessage($folder, $msg);
    }

    /**
     * Deletes messages from the specified folder.
     *
     * @param string  $folder  The folder to delete messages from.
     * @param integer $uids    IMAP message ids.
     *
     * @return NULL
     */
    public function deleteMessages($folder, $uids)
    {
        $this->_driver->deleteMessages($folder, $uids);
    }

    /**
     * Moves a message to a new folder.
     *
     * @param integer $uid         IMAP message id.
     * @param string  $old_folder  Source folder.
     * @param string  $new_folder  Target folder.
     *
     * @return NULL
     */
    public function moveMessage($uid, $old_folder, $new_folder)
    {
        $this->_driver->moveMessage($uid, $old_folder, $new_folder);
    }

    /**
     * Expunges messages in the current folder.
     *
     * @param string $folder The folder to expunge.
     *
     * @return NULL
     */
    public function expunge($folder)
    {
        $this->_driver->expunge($folder);
    }
}