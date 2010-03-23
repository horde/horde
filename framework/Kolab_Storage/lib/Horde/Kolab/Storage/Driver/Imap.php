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
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Imap extends Horde_Kolab_Storage_Driver
{
    /**
     * The IMAP connection
     *
     * @var Horde_Imap_Client
     */
    private $_imap;

    /**
     * Constructor.
     *
     * @param array  $params Connection parameters.
     */
    public function __construct($params = array())
    {
        if (isset($params['driver'])) {
            $driver = $params['driver'];
            unset($params['driver']);
        } else {
            $driver = 'socket';
        }

        $this->_imap = Horde_Imap_Client::factory($driver, $params);
    }

    /**
     * Retrieves a list of mailboxes on the server.
     *
     * @return array The list of mailboxes.
     */
    public function getMailboxes()
    {
        return $this->_imap->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));
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
        $this->_imap->openMailbox($folder, Horde_Imap_Client::OPEN_AUTO);
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
    function status($folder)
    {
        return $this->_imap->status($folder,
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
    function getUids($folder)
    {
        $search_query = new Horde_Imap_Client_Search_Query();
        $search_query->flag('DELETED', false);
        $uidsearch = $this->_imap->search($folder, $search_query);
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
        return $this->_imap->createMailbox($folder);
    }

    /**
     * Delete the specified folder.
     *
     * @param string $folder  The folder to delete.
     *
     * @return mixed True in case the operation was successfull, a
     *               PEAR error otherwise.
     */
    function delete($folder)
    {
        return $this->_imap->deleteMailbox($folder);
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
    function rename($old, $new)
    {
        return $this->_imap->renameMailbox($old, $new);
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
    function appendMessage($mailbox, $msg)
    {
        return $this->_imap->append($mailbox, array(array('data' => $msg)));
    }

    /**
     * Deletes messages from the current folder.
     *
     * @param integer $uids  IMAP message ids.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    function deleteMessages($mailbox, $uids)
    {
        if (!is_array($uids)) {
            $uids = array($uids);
        }
        return $this->_imap->store($mailbox, array('add' => array('\\deleted'), 'ids' => $uids));
    }

    /**
     * Moves a message to a new folder.
     *
     * @param integer $uid        IMAP message id.
     * @param string $new_folder  Target folder.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    function moveMessage($old_folder, $uid, $new_folder)
    {
        $options = array('ids' => array($uid), 'move' => true);
        return $this->_imap->copy($old_folder, $new_folder, $options);
    }

    /**
     * Expunges messages in the current folder.
     *
     * @param string $mailbox The mailbox to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    function expunge($mailbox)
    {
        return $this->_imap->expunge($mailbox);
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
        $result = $this->_imap->fetch($mailbox, $criteria, $options);
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
        $result = $this->_imap->fetch($mailbox, $criteria, $options);
        return $result[$uid]['bodytext'][0];
    }

    /**
     * Retrieve the access rights from a folder
     *
     * @param string $folder  The folder to retrieve the ACLs from.
     *
     * @return mixed An array of rights if successfull, a PEAR error
     * otherwise.
     */
    function getACL($folder)
    {
        if (!$this->_imap->queryCapability('ACL')) {
            $acl = array();
            $acl[Horde_Auth::getAuth()] = 'lrid';
            return $acl;
        }

        try {
            return $this->_imap->getACL($folder);
        } catch (Exception $e) {
            try {
                return array(Horde_Auth::getAuth() => str_split($this->_imap->getMyACLRights($folder)));
            } catch (Exception $e) {
                return array(Horde_Auth::getAuth() => str_split('lrid'));
            }
        }            
    }

    /**
     * Set the access rights for a folder
     *
     * @param string $folder  The folder to retrieve the ACLs from.
     * @param string $user    The user to set the ACLs for
     * @param string $acl     The ACLs
     *
     * @return mixed True if successfull, a PEAR error otherwise.
     */
    function setACL($folder, $user, $acl)
    {
        return $this->_imap->setACL($folder, $user, array('rights' => $acl));
    }

    /**
     * Fetches the annotation on a folder.
     *
     * @param string $entry         The entry to fetch.
     * @param string $mailbox_name  The name of the folder.
     *
     * @return mixed  The annotation value or a PEAR error in case of an error.
     */
    function getAnnotation($entry, $mailbox_name)
    {
        try {
            $result = $this->_imap->getMetadata($mailbox_name, $entry);
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
        return $this->_imap->setMetadata($mailbox_name,
                                         array($entry => $value));
    }


    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_imap->queryCapability('NAMESPACE') === true) {
            return new Horde_Kolab_Storage_Namespace_Imap(
                $this->_imap->getNamespaces(),
                isset($this->_params['namespaces']) ? $this->_params['namespaces'] : array()
            );
        } else if (isset($this->_params['namespaces'])) {
            return new Horde_Kolab_Storage_Namespace_Config(
                $this->_params['namespaces']
            );
        }
        return new Horde_Kolab_Storage_Namespace_Fixed();
    }
}