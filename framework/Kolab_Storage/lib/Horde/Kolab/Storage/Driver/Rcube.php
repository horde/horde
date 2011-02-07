<?php
/**
 * A Roundcube Imap based Kolab storage driver.
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
 * A Roundcube Imap based Kolab storage driver.
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
class Horde_Kolab_Storage_Driver_Rcube
extends Horde_Kolab_Storage_Driver_Base
{
    /**
     * Debug log
     *
     * @var resource
     */
    private $_debug_log;

    /**
     * Write a line of debugging output to the log.
     *
     * @return NULL
     */
    public function debugLog($driver, $message)
    {
        fwrite($this->_debug_log, $message . "\n");
    }
        
    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (is_resource($this->_debug_log)) {
            fflush($this->_debug_log);
            fclose($this->_debug_log);
            $this->_debug_log = null;
        }
    }

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        $config = $this->getParams();
        $client = new rcube_imap_generic();
        if (isset($config['debug'])) {
            if ($config['debug'] == 'STDOUT') {
                $client->setDebug(true);
            } else {
                $this->_debug_log = fopen($config['debug'], 'a');
                $client->setDebug(true, array($this, 'debugLog'));
            }
        }
        $client->connect(
            $config['host'], $config['username'], $config['password'],
            array(
                'ssl_mode' => $config['secure'],
                'port' => $config['port'],
                'timeout' => 0,
                'force_caps' => false,
            )
        );
        return $client;
    }

    /**
     * Retrieves a list of mailboxes on the server.
     *
     * @return array The list of mailboxes.
     */
    public function getMailboxes()
    {
        return $this->decodeList($this->getBackend()->listMailboxes('', '*'));
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
        $this->getBackend()->createFolder($this->encodePath($folder));
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Creating folder %s failed. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
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
        $this->getBackend()->deleteFolder($this->encodePath($folder));
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Deleting folder %s failed. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
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
        $this->getBackend()->renameFolder(
            $this->encodePath($old),
            $this->encodePath($new)
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Renaming folder %s to %s failed. Error: %s"
                    ),
                    $old,
                    $new,
                    $this->getBackend()->error
                )
            );
        }
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        return $this->getBackend()->getCapability('ACL');
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
        $acl = $this->getBackend()->getACL($this->encodePath($folder));
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed reading ACL on folder %s. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        $result = array();
        foreach ($acl as $user => $rights) {
            $result[$user] = join('', $rights);
        }
        return $result;
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
        $result = $this->getBackend()->myRights($this->encodePath($folder));
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed reading user rights on folder %s. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        return join('', $result);
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
        $this->getBackend()->setACL($this->encodePath($folder), $user, $acl);
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed setting ACL on folder %s for user %s to %s. Error: %s"
                    ),
                    $folder,
                    $user,
                    $acl,
                    $this->getBackend()->error
                )
            );
        }
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
        $this->getBackend()->deleteACL($this->encodePath($folder), $user);
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed deleting ACL on folder %s for user %s. Error: %s"
                    ),
                    $folder,
                    $user,
                    $this->getBackend()->error
                )
            );
        }
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
        list($entry, $value) = $this->_getAnnotateMoreEntry($annotation);
        $result = $this->getBackend()->getAnnotation('*', $entry, $value);
        if (empty($result)) {
            return array();
        }
        $data = array();
        foreach ($result as $folder => $annotations) {
            if (isset($annotations[$annotation])) {
                $data[$folder] = $annotations[$annotation];
            }
        }
        return $this->decodeListKeys($data);
    }

    /**
     * Fetches the annotation from a folder.
     *
     * @param string $mailbox    The name of the folder.
     * @param string $annotation The annotation to get.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($mailbox, $annotation)
    {
        list($attr, $type) = $this->_getAnnotateMoreEntry($annotation);
        $result = $this->getBackend()->getAnnotation(
            $this->encodePath($mailbox), $attr, $type
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Setting annotation %s[%s] on folder %s failed. Error: %s"
                    ),
                    $attr,
                    $type,
                    $mailbox,
                    $this->getBackend()->error
                )
            );
        }
        return $result[$mailbox][$annotation];
    }

    /**
     * Sets the annotation on a folder.
     *
     * @param string $mailbox    The name of the folder.
     * @param string $annotation The annotation to set.
     * @param array  $value      The values to set
     *
     * @return NULL
     */
    public function setAnnotation($mailbox, $annotation, $value)
    {
        list($attr, $type) = $this->_getAnnotateMoreEntry($annotation);
        $this->getBackend()->setAnnotation(
            $this->encodePath($mailbox), array(array($attr, $type, $value))
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Setting annotation %s[%s] on folder %s to %s failed. Error: %s"
                    ),
                    $attr,
                    $type,
                    $mailbox,
                    $value,
                    $this->getBackend()->error
                )
            );
        }
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->getBackend()->getCapability('NAMESPACE') === true) {
            $namespaces = array();
            foreach ($this->getBackend()->getNamespace() as $type => $elements) {
                if (is_array($elements)) {
                    foreach ($elements as $namespace) {
                        $namespace['name'] = $namespace[0];
                        $namespace['delimiter'] = $namespace[1];
                        $namespace['type'] = $type;
                        $namespaces[] = $namespace;
                    }
                }
            }
            $this->_namespace = $this->getFactory()->createNamespace('imap', $this->getAuth(), $namespaces);
        }
        return parent::getNamespace();
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
        $this->getBackend()->select($this->encodePath($folder));
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Selecting folder %s failed. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
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
        $result = $this->getBackend()->status(
            $this->encodePath($folder),
            array('UIDVALIDITY', 'UIDNEXT')
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Retrieving the status for folder %s failed. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        return array(
            'uidvalidity' => $result['UIDVALIDITY'],
            'uidnext' => $result['UIDNEXT']
        );
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
        $uids = $this->getBackend()->search(
            $this->encodePath($folder),
            'UNDELETED',
            true
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed retrieving UIDs for folder %s. Error: %s"
                    ),
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        if (!is_array($uids)) {
            $uids = array();
        }
        return $uids;
    }

    /**
     * Appends a message to the current folder.
     *
     * @param string $mailbox The mailbox to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     * @param string $msg     The message to append.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function appendMessage($mailbox, $msg)
    {
        return $this->getBackend()->append($mailbox, array(array('data' => $msg)));
    }

    /**
     * Deletes messages from the current folder.
     *
     * @param integer $uids  IMAP message ids.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function deleteMessages($mailbox, $uids)
    {
        if (!is_array($uids)) {
            $uids = array($uids);
        }
        return $this->getBackend()->store($mailbox, array('add' => array('\\deleted'), 'ids' => $uids));
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
        return $this->getBackend()->copy($old_folder, $new_folder, $options);
    }

    /**
     * Expunges messages in the current folder.
     *
     * @param string $mailbox The mailbox to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function expunge($mailbox)
    {
        return $this->getBackend()->expunge($mailbox);
    }

    /**
     * Retrieves the message headers for a given message id.
     *
     * @param string $mailbox The mailbox to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     * @param int $uid                The message id.
     * @param boolean $peek_for_body  Prefetch the body.
     *
     * @return mixed  The message header or a PEAR error in case of an error.
     */
    function getMessageHeader($mailbox, $uid, $peek_for_body = true)
    {
        $options = array('ids' => array($uid));
        $criteria = array(
            Horde_Imap_Client::FETCH_HEADERTEXT => array(
                array(
                )
            )
        );
        $result = $this->getBackend()->fetch($mailbox, $criteria, $options);
        return $result[$uid]['headertext'][0];
    }

    /**
     * Retrieves the message body for a given message id.
     *
     * @param string $mailbox The mailbox to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     * @param integet $uid  The message id.
     *
     * @return mixed  The message body or a PEAR error in case of an error.
     */
    function getMessageBody($mailbox, $uid)
    {
        $options = array('ids' => array($uid));
        $criteria = array(
            Horde_Imap_Client::FETCH_BODYTEXT => array(
                array(
                )
            )
        );
        $result = $this->getBackend()->fetch($mailbox, $criteria, $options);
        return $result[$uid]['bodytext'][0];
    }
}