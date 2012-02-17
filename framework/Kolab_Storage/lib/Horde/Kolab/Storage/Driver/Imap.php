<?php
/**
 * An IMAP based driver for accessing Kolab storage.
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
 * The IMAP driver class for accessing Kolab storage.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
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
     * Retrieves a list of folders from the server.
     *
     * @return array The list of folders.
     */
    public function listFolders()
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
            $result[$user] = strval($rights);
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
        return strval($this->getBackend()->getMyACLRights($folder));
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
     * Retrieves the specified annotation for the complete list of folders.
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
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to get.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($folder, $annotation)
    {
        try {
            $this->getBackend()->login();
            $result = $this->getBackend()->getMetadata($folder, $annotation);
        } catch (Exception $e) {
            return '';
        }
        return isset($result[$folder][$annotation]) ? $result[$folder][$annotation] : '';
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
        $this->getBackend()->login();
        try {
            return $this->getBackend()->setMetadata(
                $folder, array($annotation => $value)
            );
        } catch (Horde_Imap_Client_Exception $e) {
            throw new Horde_Kolab_Storage_Exception($e);
        }
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

                    switch ($namespace['type']) {
                    case Horde_Imap_Client::NS_PERSONAL:
                        $namespace['type'] = Horde_Kolab_Storage_Folder_Namespace::PERSONAL;
                        break;

                    case Horde_Imap_Client::NS_OTHER:
                        $namespace['type'] = Horde_Kolab_Storage_Folder_Namespace::OTHER;
                        break;

                    case Horde_Imap_Client::NS_SHARED:
                        $namespace['type'] = Horde_Kolab_Storage_Folder_Namespace::SHARED;
                        break;
                    }

                    $c[] = $namespace;
                }
                $this->_namespace = $this->getFactory()->createNamespace('imap', $this->getAuth(), $c);
            }
        }
        return parent::getNamespace();
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
        // @todo: Condstore
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
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->fullText();

        $ret = $this->getBackend()->fetch(
            $folder,
            $query,
            array('ids' => new Horde_Imap_Client_Ids($uid))
        );
        $msg = $ret[$uid]->getFullMsg();
        return array(
            Horde_Mime_Headers::parseHeaders($msg),
            Horde_Mime_Part::parseMessage($msg)
        );
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
        if (empty($uids)) {
            return array();
        }

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->structure();

        $ret = $this->getBackend()->fetch(
            $folder,
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
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uid                 The message UID.
     * @param array  $id                  The mime part ID.
     *
     * @return resource|string The body part, as a stream resource or string.
     */
    public function fetchBodypart($folder, $uid, $id)
    {
        $query = new Horde_Imap_Client_Fetch_Query();
        $query->bodyPart($id);

        $ret = $this->getBackend()->fetch(
            $folder,
            $query,
            array('ids' => new Horde_Imap_Client_Ids($uid))
        );

        return $ret[$uid]->getBodyPart($id, true);
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
        $result = $this->getBackend()->append($folder, array(array('data' => $msg)));
        return $result->ids[0];
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
        return $this->getBackend()->store($folder, array(
            'add' => array('\\deleted'),
            'ids' => new Horde_Imap_Client_Ids($uids)
        ));
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
        $options = array('ids' => new Horde_Imap_Client_Ids($uid), 'move' => true);
        return $this->getBackend()->copy($old_folder, $new_folder, $options);
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
        return $this->getBackend()->expunge($folder);
    }
}
