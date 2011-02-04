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
class Horde_Kolab_Storage_Driver_Pear
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
        if (isset($config['secure']) && $config['secure'] == 'ssl') {
            $prefix = 'ssl://';
        } else {
            $prefix = '';
        }
        $client = new Net_IMAP(
            $prefix . $config['host'],
            $config['port'],
            isset($config['secure']) && $config['secure'] == 'tls'
        );
        $client->_useUTF_7 = false;
        if (isset($config['debug'])) {
            if ($config['debug'] == 'STDOUT') {
                $client->setDebug(true);
            } else {
                throw new Horde_Kolab_Storage_Exception('This driver does not support debug logging into a file.');
            }
        }
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $client->login($config['username'], $config['password'], true, false)
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
        $list = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getMailboxes()
        );
        return $this->decodeList($list);
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->createMailbox($this->encodePath($folder))
        );
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->deleteMailbox($this->encodePath($folder))
        );
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->renameMailbox(
                $this->encodePath($old),
                $this->encodePath($new)
            )
        );
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        return $this->getBackend()->hasCapability('ACL');
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
        $result = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getACL($this->encodePath($folder))
        );
        $acl = array();
        foreach ($result as $user) {
            $acl[$user['USER']] = $user['RIGHTS'];
        }
        return $acl;
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
        return Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getMyRights($this->encodePath($folder))
        );
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->setACL($this->encodePath($folder), $user, $acl)
        );
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->deleteACL($this->encodePath($folder), $user)
        );
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
        $result = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getAnnotation($entry, $value, '*')
        );
        foreach ($result as $element) {
            if (isset($element['ATTRIBUTES'][$value])) {
                $list[$element['MAILBOX']] = $element['ATTRIBUTES'][$value];
            }
        }
        return $this->decodeListKeys($list);
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
        list($entry, $type) = $this->_getAnnotateMoreEntry($annotation);
        $result = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getAnnotation(
                $entry, $type, $this->encodePath($mailbox)
            )
        );var_dump($result);
        foreach ($result as $element) {
            if (isset($element['ATTRIBUTES'][$type])) {
                return $element['ATTRIBUTES'][$type];
            }
        }
        return '';
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
        list($entry, $type) = $this->_getAnnotateMoreEntry($annotation);
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->setAnnotation(
                $entry, array($type => $value), $this->encodePath($mailbox)
            )
        );
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->getBackend()->hasCapability('NAMESPACE') === true) {
            $namespaces = array();
            foreach ($this->getBackend()->getNamespace() as $type => $elements) {
                foreach ($elements as $namespace) {
                    switch ($type) {
                    case 'others':
                        $namespace['type'] = 'other';
                        break;
                    default:
                        $namespace['type'] = $type;
                        break;
                    }
                    $namespace['delimiter'] = $namespace['delimter'];
                    $namespaces[] = $namespace;
                }
            }
            return new Horde_Kolab_Storage_Folder_Namespace_Imap(
                $this->getAuth(),
                $namespaces,
                $this->getParam('namespaces', array())
            );
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
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->selectMailbox($this->encodePath($folder))
        );
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
        $result = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getStatus($this->encodePath($folder))
        );
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
        $this->select($folder);
        $uids = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->search('UNDELETED', true)
        );
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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->headerText();

        $result = $this->getBackend()->fetch($mailbox, $query, $options);
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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyText();

        $result = $this->getBackend()->fetch($mailbox, $query, $options);
        return $result[$uid]['bodytext'][0];
    }

}
