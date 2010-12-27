<?php
/**
 * An PEAR-Net_Imap based Kolab storage driver.
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
 * An PEAR-Net_Imap based Kolab storage driver.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Driver_Pear
extends Horde_Kolab_Storage_Driver_Base
{
    /**
     * The IMAP client.
     *
     * @var Net_IMAP
     */
    private $_imap;

    /**
     * Constructor.
     *
     * @param Net_IMAP                    $imap    The IMAP connection handler.
     * @param Horde_Kolab_Storage_Factory $factory A factory for helper objects.
     * @param array                       $params  Connection parameters.
     */
    public function __construct(
        Net_IMAP $imap,
        Horde_Kolab_Storage_Factory $factory,
        $params = array()
    ) {
        $this->_imap = $imap;
        parent::__construct($factory, $params);
    }

    /**
     * Return the id of the user currently authenticated.
     *
     * @return string The id of the user that opened the IMAP connection.
     */
    public function getAuth()
    {
        return $this->getParam('username');
    }

    /**
     * Retrieves a list of mailboxes on the server.
     *
     * @return array The list of mailboxes.
     */
    public function getMailboxes()
    {
        return $this->_imap->getMailboxes();
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
        $list = array();
        $result = $this->_imap->getAnnotation($entry, $value, '*');
        foreach ($result as $element) {
            if (isset($element['ATTRIBUTES'][$value])) {
                $list[$element['MAILBOX']] = $element['ATTRIBUTES'][$value];
            }
        }
        return $list;
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
    public function status($folder)
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
    public function getUids($folder)
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
    public function delete($folder)
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
    public function rename($old, $new)
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
    public function appendMessage($mailbox, $msg)
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
    public function deleteMessages($mailbox, $uids)
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
    public function moveMessage($old_folder, $uid, $new_folder)
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
    public function expunge($mailbox)
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
     * Retrieve the access rights for a folder.
     *
     * @param Horde_Kolab_Storage_Folder $folder The folder to retrieve the ACL for.
     *
     * @return An array of rights.
     */
    public function getAcl(Horde_Kolab_Storage_Folder $folder)
    {
        //@todo: Separate driver class
        if ($this->_imap->queryCapability('ACL') === true) {
            if ($folder->getOwner() == $this->getAuth()) {
                try {
                    return $this->_getAcl($folder->getName());
                } catch (Exception $e) {
                    return array($this->getAuth() => $this->_getMyAcl($folder->getName()));
                }
            } else {
                $acl = $this->_getMyAcl($folder->getName());
                if (strpos($acl, 'a')) {
                    try {
                        return $this->_getAcl($folder->getName());
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
        $acl = $this->_imap->getACL($folder);
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
        return $this->_imap->getMyACLRights($folder);
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
        if ($this->_imap->queryCapability('ACL') === true) {
            $this->_imap->setACL($folder, $user, array('rights' => $acl));
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
        if ($this->_imap->queryCapability('ACL') === true) {
            $this->_imap->setACL($folder, $user, array('remove' => true));
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
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_imap->hasCapability('NAMESPACE') === true) {
            $namespaces = array();
            foreach ($this->_imap->getNamespace() as $type => $elements) {
                foreach ($elements as $namespace) {
                    switch ($type) {
                    case 'personal':
                        $namespace['type'] = 'personal';
                        break;
                    case 'others':
                        $namespace['type'] = 'other';
                        break;
                    case 'shared':
                        $namespace['type'] = 'shared';
                        break;
                    }
                    $namespace['delimiter'] = $namespace['delimter'];
                    $namespaces[] = $namespace;
                }
            }
            return new Horde_Kolab_Storage_Folder_Namespace_Imap(
                $namespaces,
                $this->getParam('namespaces', array())
            );
        }
        return parent::getNamespace();
    }
}