<?php
/**
 * An Kolab storage mock driver.
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
 * An Kolab storage mock driver.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Mock
extends Horde_Kolab_Storage_Driver_Base
{
    /** Flag to indicated a deleted message*/
    const FLAG_DELETED = 1;

    /**
     * The data of the folders.
     *
     * @var Horde_Kolab_Storage_Driver_Mock_Data
     */
    private $_data;

    /**
     * The regular expression for converting folder names.
     *
     * @var string
     */
    private $_conversion_pattern;

    /**
     * The data of the folder currently opened
     *
     * @var array
     */
    private $_mbox = null;

    /**
     * The name of the folder currently opened
     *
     * @var array
     */
    private $_mboxname = null;

    /**
     * A list of groups (associates users [key] with an array of group names
     * [value]).
     *
     * @var array
     */
    private $_groups = array();

    /**
     * The currently selected folder.
     *
     * @var string
     */
    private $_selected;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Factory $factory A factory for helper objects.
     * @param array $params                        Connection parameters.
     */
    public function __construct(
        Horde_Kolab_Storage_Factory $factory,
        $params = array()
    ) {
        if (isset($params['data'])) {
            if (is_array($params['data'])) {
                $params['data'] = new Horde_Kolab_Storage_Driver_Mock_Data(
                    $params['data']
                );
            }
            $this->_data = $params['data'];
            unset($params['data']);
        } else {
            $this->_data = new Horde_Kolab_Storage_Driver_Mock_Data(array());
        }
        parent::__construct($factory, $params);
    }

    /**
     * Convert the external folder id to an internal folder name.
     *
     * @param string $folder The external folder name.
     *
     * @return string The internal folder id.
     */
    private function _convertToInternal($folder)
    {
        if (substr($folder, 0, 5) == 'INBOX') {
            $user = explode('@', $this->getAuth());
            return 'user/' . $user[0] . substr($folder, 5);
        }
        return $folder;
    }

    /**
     * Convert the internal folder name into an external folder id.
     *
     * @param string $mbox The internal folder name.
     *
     * @return string The external folder id.
     */
    private function _convertToExternal($mbox)
    {
        if ($this->_conversion_pattern === null) {
            if ($this->getAuth() != '') {
                $user = explode('@', $this->getAuth());
                $this->_conversion_pattern = '#^user/' . $user[0] . '#';
            } else {
                /**
                 * @todo: FIXME, this is a hack for the current state of the
                 * Kolab share driver which does not yet know how to properly
                 * deal with system shares.
                 */
                if ($mbox == 'user/') {
                    return 'INBOX';
                } else {
                    return preg_replace('#^user//#', 'INBOX/', $mbox);
                }
            }
        }
        return preg_replace($this->_conversion_pattern, 'INBOX', $mbox);
    }

    /**
     * Set a group list.
     *
     * @param array $groups A list of groups. User names are the keys, an array
     *                      of group names are the values.
     *
     * @return NULL
     */
    public function setGroups($groups)
    {
        $this->_groups = $groups;
    }

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
    }

    /**
     * Return the unique connection id.
     *
     * @return string The connection id.
     */
    public function getId()
    {
        return $this->getAuth() . '@mock:0';
    }

    /**
     * Retrieves a list of folders on the server.
     *
     * @return array The list of folders.
     */
    public function listFolders()
    {
        $result = array();
        foreach ($this->_data->arrayKeys() as $mbox) {
            if ($this->_folderVisible($mbox, $this->getAuth())) {
                $result[] = $this->_convertToExternal($mbox);
            }
        }
        return $result;
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
        $folder = $this->_convertToInternal($folder);
        if (isset($this->_data[$folder])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf("IMAP folder %s does already exist!", $folder)
            );
        }
        $this->_data[$folder] = array(
            'status' => array(
                'uidvalidity' => time(),
                'uidnext' => 1
            ),
            'mails' => array(),
            'permissions' => array($this->getAuth() => 'lrswipkxtecda'),
            'annotations' => array(),
        );
    }

    /**
     * Delete the specified folder.
     *
     * @param string $folder The folder to delete.
     *
     * @return NULL
     */
    public function delete($folder)
    {
        $folder = $this->_convertToInternal($folder);
        if (!isset($this->_data[$folder])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf("IMAP folder %s does not exist!", $folder)
            );
        }
        unset($this->_data[$folder]);
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
        $old = $this->_convertToInternal($old);
        $new = $this->_convertToInternal($new);
        if (!isset($this->_data[$old])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf("IMAP folder %s does not exist!", $old)
            );
        }
        if (isset($this->_data[$new])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf("IMAP folder %s does already exist!", $new)
            );
        }
        $this->_data[$new] = $this->_data[$old];
        unset($this->_data[$old]);
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        return true;
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
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        $this->_failOnNoAdmin($folder);
        if ($this->_data->hasPermissions($folder)) {
            return $this->_data->getPermissions($folder);
        }
        return array();
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
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        $myacl = array();
        $users = array($this->getAuth(), 'anyone', 'anonymous');
        if (isset($this->_groups[$this->getAuth()])) {
            foreach ($this->_groups[$this->getAuth()] as $group) {
                $users[] = 'group:' . $group;
            }
        }
        foreach ($users as $user) {
            if ($this->_data->hasUserPermissions($folder, $user)) {
                $myacl = array_merge($myacl, str_split($this->_data->getUserPermissions($folder, $user)));
            }
        }
        return join('', $myacl);
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
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        $this->_failOnNoAdmin($folder);
        $this->_data->setUserPermissions($folder, $user, $acl);
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
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        $this->_failOnNoAdmin($folder);
        if ($this->_data->hasUserPermissions($folder, $user)) {
            $this->_data->deleteUserPermissions($folder, $user);
        }
    }

    /**
     * Retrieves the specified annotation for the complete list of folders.
     *
     * @param string $annotation The name of the annotation to retrieve.
     *
     * @return array An associative array combining the folder names as key with
     *               the corresponding annotation value.
     */
    public function listAnnotation($annotation)
    {
        $result = array();
        foreach ($this->_data->arrayKeys() as $folder) {
            if ($this->_data->hasAnnotation($folder, $annotation)) {
                $result[$this->_convertToExternal($folder)] = $this->_data->getAnnotation($folder, $annotation);
            }
        }
        return $result;
    }

    /**
     * Fetches the annotation from a folder.
     *
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to get.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($folder, $annotation)
    {
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        if ($this->_data->hasAnnotation($folder, $annotation)) {
            return $this->_data->getAnnotation($folder, $annotation);
        }
        return '';
    }

    /**
     * Sets the annotation on a folder.
     *
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to set.
     * @param array  $value      The values to set
     *
     * @return NULL
     */
    public function setAnnotation($folder, $annotation, $value)
    {
        $folder = $this->_convertToInternal($folder);
        $this->_failOnMissingFolder($folder);
        $this->_data->setAnnotation($folder, $annotation, $value);
    }

    /**
     * Error out in case the provided folder is missing.
     *
     * @param string  $folder The folder.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case the folder is missing.
     */
    private function _failOnMissingFolder($folder)
    {
        if (!isset($this->_data[$folder])
            || !$this->_folderVisible($folder, $this->getAuth())) {
            $this->_folderMissing($folder);
        }
    }

    /**
     * Is the folder visible to the specified user (or a global group)?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the folder is visible.
     */
    private function _folderVisible($folder, $user)
    {
        return empty($user) 
            || $this->_folderVisibleToUnique($folder, $user)
            || $this->_folderVisibleToGroup($folder, $user)
            || $this->_folderVisibleToUnique($folder, 'anyone')
            || $this->_folderVisibleToUnique($folder, 'anonymous');
    }

    /**
     * Is the folder visible to a group the user belongs to?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the folder is visible.
     */
    private function _folderVisibleToGroup($folder, $user)
    {
        if (isset($this->_groups[$user])) {
            foreach ($this->_groups[$user] as $group) {
                if ($this->_folderVisibleToUnique($folder, 'group:' . $group)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Is the folder visible to exactly the specified user?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the folder is visible.
     */
    private function _folderVisibleToUnique($folder, $user)
    {
        if ($this->_data->hasUserPermissions($folder, $user)) {
            if (strpos($this->_data->getUserPermissions($folder, $user), 'l') !== false
                || strpos($this->_data->getUserPermissions($folder, $user), 'r') !== false
                || strpos($this->_data->getUserPermissions($folder, $user), 'a') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Error out indicating that the user does not have the required
     * permissions.
     *
     * @param string  $folder The folder.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case the folder is missing.
     */
    private function _folderMissing($folder)
    {
        throw new Horde_Kolab_Storage_Exception(
            sprintf('The folder %s does not exist!', $folder)
        );
    }

    /**
     * Error out in case the user is no admin of the specified folder.
     *
     * @param string  $folder The folder.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case the user has no admin rights.
     */
    private function _failOnNoAdmin($folder)
    {
        if (!isset($this->_data[$folder])
            || !$this->_folderAdmin($folder, $this->getAuth())) {
            $this->_permissionDenied();
        }
    }

    /**
     * Is the user a folder admin (or one of the global groups)?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the user has admin rights on the folder.
     */
    private function _folderAdmin($folder, $user)
    {
        return empty($user)
            || $this->_folderAdminForUnique($folder, $user)
            || $this->_folderAdminForGroup($folder, $user)
            || $this->_folderAdminForUnique($folder, 'anyone')
            || $this->_folderAdminForUnique($folder, 'anonymous');
    }

    /**
     * Is the folder visible to a group the user belongs to?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the folder is visible.
     */
    private function _folderAdminForGroup($folder, $user)
    {
        if (isset($this->_groups[$user])) {
            foreach ($this->_groups[$user] as $group) {
                if ($this->_folderAdminForUnique($folder, 'group:' . $group)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Is the exact specified user an admin for the folder?
     *
     * @param string $folder The folder.
     * @param string $user   The user.
     *
     * @return boolean True if the user has admin rights on the folder.
     */
    private function _folderAdminForUnique($folder, $user)
    {
        if ($this->_data->hasUserPermissions($folder, $user)
            && strpos($this->_data->getUserPermissions($folder, $user), 'a') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Error out indicating that the user does not have the required
     * permissions.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_Storage_Exception In case the folder is missing.
     */
    private function _permissionDenied()
    {
        throw new Horde_Kolab_Storage_Exception('Permission denied!');
    }
    
    /**
     * Opens the given folder.
     *
     * @param string $folder  The folder to open
     *
     * @return NULL
     */
    public function select($folder)
    {
        $folder = $this->_convertToInternal($folder);
        if (!isset($this->_data[$folder])
            || $this->_selected !== $this->_data[$folder]) {
            if (!isset($this->_data[$folder])) {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf('Folder %s does not exist!', $folder)
                );
            }
            $this->_selected = $this->_data[$folder];
        }
    }

    /**
     * Returns the status of the current folder.
     *
     * @param string $folder Check the status of this folder.
     *
     * @return array  An array that contains 'uidvalidity' and 'uidnext'.
     */
    public function status($folder)
    {
        $this->select($folder);
        return $this->_selected['status'];
    }

    /**
     * Returns the message ids of the messages in this folder.
     *
     * @param string $folder Check the status of this folder.
     *
     * @return array  The message ids.
     */
    public function getUids($folder)
    {
        $this->select($folder);
        return array_keys(
            array_filter($this->_selected['mails'], array($this, '_notDeleted'))
        );
    }

    /**
     * Indicates if a message is considered deleted.
     *
     * @param array $message The message information.
     *
     * @return boolean True if the message has not been marked as deleted.
     */
    public function _notDeleted($message)
    {
        return !isset($message['flags'])
            || !($message['flags'] & self::FLAG_DELETED);
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
        return $this->getParser()->fetch($folder, $uids, $options);
    }

    /**
     * Retrieves the messages for the given message ids.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uids                The message UIDs.
     *
     * @return array An array of message structures parsed into Horde_Mime_Part
     *               instances.
     */
    public function fetchStructure($folder, $uids)
    {
        $this->select($folder);
        $result = array();
        foreach ($uids as $uid) {
            $result[$uid]['structure'] = $this->_selected['mails'][$uid]['structure'];
        }
        return $result;
    }

    /**
     * Retrieves a bodypart for the given message ID and mime part ID.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uid                 The message UID.
     * @param array  $id                  The mime part ID.
     *
     * @return resource|string The body part, as a stream resource or string.
     */
    public function fetchBodypart($folder, $uid, $id)
    {
        $this->select($folder);
        if (isset($this->_selected['mails'][$uid]['parts'][$id])) {
            if (isset($this->_selected['mails'][$uid]['parts'][$id]['file'])) {
                return fopen(
                    $this->_selected['mails'][$uid]['parts'][$id]['file'],
                    'r'
                );
            }
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'No such part %s for message uid %s in folder %s!',
                    $id,
                    $uid,
                    $folder
                )
            );
        }
    }

    /**
     * Appends a message to the current folder.
     *
     * @param string $folder The folder to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     * @param string $msg     The message to append.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function appendMessage($folder, $msg)
    {
        return $this->_imap->append($folder, array(array('data' => $msg)));
    }

    /**
     * Deletes messages from the current folder.
     *
     * @param integer $uids  IMAP message ids.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function deleteMessages($folder, $uids)
    {
        if (!is_array($uids)) {
            $uids = array($uids);
        }
        return $this->_imap->store($folder, array('add' => array('\\deleted'), 'ids' => $uids));
    }

    /**
     * Moves a message to a new folder.
     *
     * @param integer $uid        IMAP message id.
     * @param string $new_folder  Target folder.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function moveMessage($old_folder, $uid, $new_folder)
    {
        $options = array('ids' => array($uid), 'move' => true);
        return $this->_imap->copy($old_folder, $new_folder, $options);
    }

    /**
     * Expunges messages in the current folder.
     *
     * @param string $folder The folder to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function expunge($folder)
    {
        return $this->_imap->expunge($folder);
    }
}
