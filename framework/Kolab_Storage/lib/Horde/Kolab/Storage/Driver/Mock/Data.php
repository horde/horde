<?php
/**
 * Data storage for the mock driver.
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
 * Data storage for the mock driver.
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
class Horde_Kolab_Storage_Driver_Mock_Data
implements ArrayAccess
{
    /** Flag to indicated a deleted message*/
    const FLAG_DELETED = 1;

    /**
     * The data array.
     *
     * @var array
     */
    private $_data;

    /**
     * The currently selected folder.
     *
     * @var string
     */
    private $_selected;

    /**
     * Constructor.
     *
     * @param array $data The initial data.
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * Returns the value of the given offset in this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return mixed The data value.
     */
    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    /**
     * Sets the value of the given offset in this array.
     *
     * @param string|int $offset The array offset.
     * @param mi $offset The array offset.
     *
     * @return NULL
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Tests if the value of the given offset exists in this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return boolean True if the offset exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Removes the given offset exists from this array.
     *
     * @param string|int $offset The array offset.
     *
     * @return NULL
     */
    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * Returns the array keys of this array.
     *
     * @return array The keys of this array.
     */
    public function arrayKeys()
    {
        return array_keys($this->_data);
    }

    public function hasPermissions($folder)
    {
        return isset($this->_data[$folder]['permissions']);
    }

    public function getPermissions($folder)
    {
        return $this->_data[$folder]['permissions'];
    }

    public function hasUserPermissions($folder, $user)
    {
        return isset($this->_data[$folder]['permissions'][$user]);
    }

    public function getUserPermissions($folder, $user)
    {
        return $this->_data[$folder]['permissions'][$user];
    }

    public function setUserPermissions($folder, $user, $acl)
    {
        $this->_data[$folder]['permissions'][$user] = $acl;
    }

    public function deleteUserPermissions($folder, $user)
    {
        unset($this->_data[$folder]['permissions'][$user]);
    }

    public function hasAnnotation($folder, $annotation)
    {
        return isset($this->_data[$folder]['annotations'][$annotation]);
    }

    public function getAnnotation($folder, $annotation)
    {
        return $this->_data[$folder]['annotations'][$annotation];
    }

    public function setAnnotation($folder, $annotation, $value)
    {
        $this->_data[$folder]['annotations'][$annotation] = $value;
    }

    public function deleteAnnotation($folder, $annotation)
    {
        unset($this->_data[$folder]['annotations'][$annotation]);
    }

    public function select($folder)
    {
        if (!isset($this->_data[$folder])) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Folder %s does not exist!', $folder)
            );
        }
        if ($this->_selected !== $this->_data[$folder]) {
            $this->_selected = &$this->_data[$folder];
        }
    }

    public function status($folder)
    {
        $this->select($folder);
        return $this->_selected['status'];
    }

    public function getUids($folder)
    {
        $this->select($folder);
        if (empty($this->_selected['mails'])) {
            return array();
        } else {
            return array_keys(
                array_filter($this->_selected['mails'], array($this, '_notDeleted'))
            );
        }
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
            if (isset($this->_selected['mails'][$uid]['structure'])) {
                $result[$uid]['structure'] = $this->_selected['mails'][$uid]['structure'];
            } else if (isset($this->_selected['mails'][$uid]['stream'])) {
                rewind($this->_selected['mails'][$uid]['stream']);
                $result[$uid]['structure'] = Horde_Mime_Part::parseMessage(
                    stream_get_contents($this->_selected['mails'][$uid]['stream'])
                );
            } else {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf(
                        'No message %s in folder %s!',
                        $uid,
                        $folder
                    )
                );
            }
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
        } else if (isset($this->_selected['mails'][$uid]['stream'])) {
            rewind($this->_selected['mails'][$uid]['stream']);
            return Horde_Mime_Part::parseMessage(
                stream_get_contents($this->_selected['mails'][$uid]['stream'])
            )->getPart($id)->getContents();
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
        rewind($msg);
        $this->select($folder);
        $this->_selected['mails'][$this->_selected['status']['uidnext']] = array(
            'flags' => 0,
            'stream' => $msg,
        );
        return $this->_selected['status']['uidnext']++;
    }

    public function deleteMessages($folder, $uids)
    {
        $this->select($folder);
        foreach ($uids as $uid) {
            $this->_selected['mails'][$uid]['flags'] |= self::FLAG_DELETED;
        }
    }

    public function moveMessage($uid, $old_folder, $new_folder)
    {
        $this->select($old_folder);
        if (!isset($this->_selected['mails'][$uid])) {
            throw new Horde_Kolab_Storage_Exception(sprintf("No IMAP message %s!", $uid));
        }
        $mail = $this->_selected['mails'][$uid];
        $this->deleteMessages($old_folder, array($uid));
        $this->appendMessage($new_folder, $mail['stream']);
        $this->expunge($old_folder);
    }

    public function expunge($folder)
    {
        $this->select($folder);
        $delete = array();
        foreach ($this->_selected['mails'] as $uid => $mail) {
            if ($mail['flags'] & self::FLAG_DELETED) {
                $delete[] = $uid;
            }
        }
        foreach ($delete as $uid) {
            unset($this->_selected['mails'][$uid]);
        }
    }
}