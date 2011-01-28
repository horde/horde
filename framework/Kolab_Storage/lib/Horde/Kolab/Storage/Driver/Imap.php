<?php
/**
 * An IMAP based driver for accessing Kolab storage.
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
 * The IMAP driver class for accessing Kolab storage.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Imap
extends Horde_Kolab_Storage_Driver_Base
{
    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        $config = $this->getParams();
        $config['hostspec'] = $config['host'];
        unset($config['host']);
        //$config['debug'] = '/tmp/imap.log';
        if ($config['driver'] = 'horde') {
            return new Horde_Imap_Client_Socket($config);
        } else {
            return new Horde_Imap_Client_Cclient($config);
        }
    }

    /**
     * Retrieves a list of mailboxes from the server.
     *
     * @return array The list of mailboxes.
     */
    public function getMailboxes()
    {
        return $this->getBackend()->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));
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
        $result = $this->getBackend()->getMetadata('*', $annotation);
        $data = array();
        foreach ($result as $folder => $annotations) {
            if (isset($annotations[$annotation])) {
                $data[$folder] = $annotations[$annotation];
            }
        }
        return $data;
    }

    /**
     * Opens the given folder.
     *
     * @param string $folder  The folder to open
     *
     * @return mixed  True in case the folder was opened successfully, a PEAR
     *                error otherwise.
     */
    public function select($folder)
    {
        $this->getBackend()->openMailbox($folder, Horde_Imap_Client::OPEN_AUTO);
        return true;
    }

    /**
     * Does the given folder exist?
     *
     * @param string $folder The folder to check.
     *
     * @return boolean True in case the folder exists, false otherwise.
     */
    public function exists($folder)
    {
        $folders = $this->getMailboxes();
        if (in_array($folder, $folders)) {
            return true;
        }
        return false;
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
        return $this->getBackend()->status($folder,
                                    Horde_Imap_Client::STATUS_UIDNEXT
                                    | Horde_Imap_Client::STATUS_UIDVALIDITY);
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
        $search_query = new Horde_Imap_Client_Search_Query();
        $search_query->flag('DELETED', false);
        $uidsearch = $this->getBackend()->search($folder, $search_query);
        $uids = $uidsearch['match'];
        return $uids;
    }

    /**
     * Create the specified folder.
     *
     * @param string $folder The folder to create.
     *
     * @return mixed True in case the operation was successfull, a
     *               PEAR error otherwise.
     */
    public function create($folder)
    {
        return $this->getBackend()->createMailbox($folder);
    }

    /**
     * Delete the specified folder.
     *
     * @param string $folder  The folder to delete.
     *
     * @return mixed True in case the operation was successfull, a
     *               PEAR error otherwise.
     */
    public function delete($folder)
    {
        return $this->getBackend()->deleteMailbox($folder);
    }

    /**
     * Rename the specified folder.
     *
     * @param string $old  The folder to rename.
     * @param string $new  The new name of the folder.
     *
     * @return mixed True in case the operation was successfull, a
     *               PEAR error otherwise.
     */
    public function rename($old, $new)
    {
        return $this->getBackend()->renameMailbox($old, $new);
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
    public function getMessageHeader($mailbox, $uid, $peek_for_body = true)
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
    public function getMessageBody($mailbox, $uid)
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

    /**
     * Retrieve the access rights for a folder.
     *
     * @param Horde_Kolab_Storage_Folder $folder The folder to retrieve the ACL for.
     *
     * @return An array of rights.
     */
    public function getAcl(Horde_Kolab_Storage_Folder $folder)
    {
        //@todo: Separate driver class
        if ($this->getBackend()->queryCapability('ACL') === true) {
            if ($folder->getOwner() == $this->getAuth()) {
                try {
                    return $this->_getAcl($folder->getPath());
                } catch (Exception $e) {
                    return array($this->getAuth() => $this->_getMyAcl($folder->getPath()));
                }
            } else {
                $acl = $this->_getMyAcl($folder->getPath());
                if (strpos($acl, 'a')) {
                    try {
                        return $this->_getAcl($folder->getPath());
                    } catch (Exception $e) {
                    }
                }
                return array($this->getAuth() => $acl);
            }
        } else {
            return array($this->getAuth() => 'lrid');
        }
    }

    /**
     * Retrieve the access rights for a folder.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @return An array of rights.
     */
    private function _getAcl($folder)
    {
        $acl = $this->getBackend()->getACL($folder);
        $result = array();
        foreach ($acl as $user => $rights) {
            $result[$user] = join('', $rights);
        }
        return $result;
    }
    
    /**
     * Retrieve the access rights on a folder for the current user.
     *
     * @param string $folder The folder to retrieve the ACL for.
     *
     * @return An array of rights.
     */
    private function _getMyAcl($folder)
    {
        return $this->getBackend()->getMyACLRights($folder);
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
        //@todo: Separate driver class
        if ($this->getBackend()->queryCapability('ACL') === true) {
            $this->getBackend()->setACL($folder, $user, array('rights' => $acl));
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
        //@todo: Separate driver class
        if ($this->getBackend()->queryCapability('ACL') === true) {
            $this->getBackend()->setACL($folder, $user, array('remove' => true));
        }
    }

    /**
     * Fetches the annotation on a folder.
     *
     * @param string $entry         The entry to fetch.
     * @param string $mailbox_name  The name of the folder.
     *
     * @return mixed  The annotation value or a PEAR error in case of an error.
     */
    public function getAnnotation($entry, $mailbox_name)
    {
        try {
            $result = $this->getBackend()->getMetadata($mailbox_name, $entry);
        } catch (Exception $e) {
            return '';
        }
        return isset($result[$mailbox_name][$entry]) ? $result[$mailbox_name][$entry] : '';
    }

    /**
     * Sets the annotation on a folder.
     *
     * @param string $entry          The entry to set.
     * @param array  $value          The values to set
     * @param string $mailbox_name   The name of the folder.
     *
     * @return mixed  True if successfull, a PEAR error otherwise.
     */
    public function setAnnotation($entry, $value, $mailbox_name)
    {
        return $this->getBackend()->setMetadata($mailbox_name,
                                         array($entry => $value));
    }


    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_namespace === null
            && $this->getBackend()->queryCapability('NAMESPACE') === true) {
            $c = array();
            $configuration = $this->getParam('namespaces', array());
            foreach ($this->getBackend()->getNamespaces() as $namespace) {
                if (in_array($namespace['name'], array_keys($configuration))) {
                    $namespace = array_merge($namespace, $configuration[$namespace['name']]);
                }
                $c[] = $namespace;
            }
            $this->_namespace = $this->getFactory()->createNamespace('imap', $this->getAuth(), $c);
        }
        return parent::getNamespace();
    }
}