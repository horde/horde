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
        if (isset($config['debug']) && $config['debug'] == 'STDOUT') {
            $config['debug'] = STDOUT;
        }
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
        return $this->decodeList(
            $this->getBackend()->listMailboxes(
                '*', Horde_Imap_Client::MBOX_ALL, array('flat' => true)
            )
        );
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
        return $this->getBackend()->createMailbox($folder);
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
        $this->getBackend()->deleteMailbox($folder);
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
        $this->getBackend()->renameMailbox($old, $new);
    }

    /**
     * Does the backend support ACL?
     *
     * @return boolean True if the backend supports ACLs.
     */
    public function hasAclSupport()
    {
        $this->getBackend()->login();
        return $this->getBackend()->queryCapability('ACL');
    }

    /**
     * Retrieve the access rights for a folder.
     *
     * @param Horde_Kolab_Storage_Folder $folder The folder to retrieve the ACL for.
     *
     * @return An array of rights.
     */
    public function getAcl($folder)
    {
        $acl = $this->getBackend()->getACL($folder);
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
        $this->getBackend()->setACL($folder, $user, array('rights' => $acl));
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
        $this->getBackend()->setACL($folder, $user, array('remove' => true));
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
        try {
            $this->getBackend()->login();
            $result = $this->getBackend()->getMetadata($mailbox, $annotation);
        } catch (Exception $e) {
            return '';
        }
        return isset($result[$mailbox][$annotation]) ? $result[$mailbox][$annotation] : '';
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
        $this->getBackend()->login();
        return $this->getBackend()->setMetadata(
            $mailbox, array($annotation => $value)
        );
    }

    /**
     * Retrieve the namespace information for this connection.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace The initialized namespace handler.
     */
    public function getNamespace()
    {
        if ($this->_namespace === null) {
            $this->getBackend()->login();
            if ( $this->getBackend()->queryCapability('NAMESPACE') === true) {
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
        }
        return parent::getNamespace();
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
     * Opens the given folder.
     *
     * @param string $folder The folder to open
     *
     * @return NULL
     */
    public function select($folder, $mode = Horde_Imap_Client::OPEN_AUTO)
    {
        $this->getBackend()->openMailbox($folder, $mode);
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
        return $this->getBackend()->status(
            $folder,
            Horde_Imap_Client::STATUS_UIDNEXT
            | Horde_Imap_Client::STATUS_UIDVALIDITY
        );
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
        $search_query = new Horde_Imap_Client_Search_Query();
        $search_query->flag('DELETED', false);
        $uidsearch = $this->getBackend()->search($folder, $search_query);
        $uids = $uidsearch['match'];
        return $uids->ids;
    }

    /**
     * Retrieves the messages for the given message ids.
     *
     * @param string $mailbox The mailbox to fetch the messages from.
     * @param array  $uids                The message UIDs.
     *
     * @return Horde_Mime_Part The message structure parsed into a
     *                         Horde_Mime_Part instance.
     */
    public function fetchStructure($mailbox, $uids)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->structure();

        $ret = $this->getBackend()->fetch(
            $mailbox,
            $query,
            array('ids' => new Horde_Imap_Client_Ids($uids))
        );

        $out = array();
        foreach (array_keys($ret) as $key) {
            $out[$key]['structure'] = $ret[$key]->getStructure();
        }

        return $out;
    }

    /**
     * Retrieves a bodypart for the given message ID and mime part ID.
     *
     * @param string $mailbox The mailbox to fetch the messages from.
     * @param array  $uid                 The message UID.
     * @param array  $id                  The mime part ID.
     *
     * @return resource  The body part, in a stream resource.
     */
    public function fetchBodypart($mailbox, $uid, $id)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyPart($id);

        $ret = $this->getBackend()->fetch(
            $mailbox,
            $query,
            array('ids' => new Horde_Imap_Client_Ids($uid))
        );

        return $ret[$uid]->getBodyPart($id, true);
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
        return $this->getBackend()->store($mailbox, array(
            'add' => array('\\deleted'),
            'ids' => new Horde_Imap_Client_Ids($uids)
        ));
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
        $options = array('ids' => new Horde_Imap_Client_Ids($uid), 'move' => true);
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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->headerText();

        $result = $this->getBackend()->fetch($mailbox, $query, array(
            'ids' => new Horde_Imap_Client_Ids($uid)
        ));

        return $result[$uid]->getHeaderText();
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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyText();

        $result = $this->getBackend()->fetch($mailbox, $query, array(
            'ids' => new Horde_Imap_Client_Ids($uid)
        ));

        return $result[$uid]->getBodyText();
    }

}
