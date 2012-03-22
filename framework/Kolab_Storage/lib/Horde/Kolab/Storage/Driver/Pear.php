<?php
/**
 * An PEAR-Net_Imap based Kolab storage driver.
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
 * An PEAR-Net_Imap based Kolab storage driver.
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
     * Retrieves a list of folders on the server.
     *
     * @return array The list of folders.
     */
    public function listFolders()
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
     * Retrieves the specified annotation for the complete list of folders.
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
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to get.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($folder, $annotation)
    {
        list($entry, $type) = $this->_getAnnotateMoreEntry($annotation);
        $result = Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getAnnotation(
                $entry, $type, $this->encodePath($folder)
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
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to set.
     * @param array  $value      The values to set
     *
     * @return NULL
     */
    public function setAnnotation($folder, $annotation, $value)
    {
        list($entry, $type) = $this->_getAnnotateMoreEntry($annotation);
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->setAnnotation(
                $entry, array($type => $value), $this->encodePath($folder)
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
        $this->select($folder);
        return array(
            Horde_Mime_Headers::parseHeaders(
                Horde_Kolab_Storage_Exception_Pear::catchError(
                    $this->getBackend()->getRawHeaders($uid, '', true)
                )
            ),
            Horde_Mime_Part::parseMessage(
                Horde_Kolab_Storage_Exception_Pear::catchError(
                    $this->getBackend()->getBody($uid, true)
                )
            )
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
        $result = array();

        $this->select($folder);
        foreach ($uids as $uid) {
            $structure = Horde_Kolab_Storage_Exception_Pear::catchError(
                $this->getBackend()->getStructure($uid, true)
            );
            $ob = $this->_parseStructure($structure);
            $ob->buildMimeIds();
            $result[$uid]['structure'] = $ob;
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
        return Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->getBodyPart($uid, $id, true)
        );
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
        return Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->appendMessage(stream_get_contents($msg))
        );
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
        $this->select($folder);
        return Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->deleteMessages($uids, true)
        );
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
        $this->select($old_folder);
        Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->copyMessage($uid, $new_folder)
        );
        $this->deleteMessages($old_folder, array($uid));
        $this->expunge($old_folder);
    }

    /**
     * Expunges messages in the current folder.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function expunge($folder)
    {
        $this->select($folder);
        return Horde_Kolab_Storage_Exception_Pear::catchError(
            $this->getBackend()->expunge()
        );
    }

    /**
     * Parse the output from imap_fetchstructure() into a MIME Part object.
     *
     * @param object $data  Data from imap_fetchstructure().
     *
     * @return Horde_Mime_Part  A MIME Part object.
     */
    protected function _parseStructure($data)
    {
        $ob = new Horde_Mime_Part();

        $ob->setType(strtolower($data->type) . '/' . strtolower($data->subType));

        // Optional for multipart-parts, required for all others
        if (isset($data->parameters)) {
            $params = array();
            foreach ($data->parameters as $key => $value) {
                $params[strtolower($key)] = $value;
            }

            $params = Horde_Mime::decodeParam('content-type', $params);
            foreach ($params['params'] as $key => $value) {
                $ob->setContentTypeParameter($key, $value);
            }
        }

        // Optional entries. 'location' and 'language' not supported
        if (isset($data->disposition)) {
            $ob->setDisposition($data->disposition);
            if (isset($data->dparameters)) {
                $dparams = array();
                foreach ($data->dparameters as $key => $value) {
                    $dparams[strtolower($key)] = $value;
                }

                $dparams = Horde_Mime::decodeParam('content-disposition', $dparams);
                foreach ($dparams['params'] as $key => $value) {
                    $ob->setDispositionParameter($key, $value);
                }
            }
        }

        if ($ob->getPrimaryType() == 'multipart') {
            // multipart/* specific entries
            foreach ($data->subParts as $val) {
                $ob->addPart($this->_parseStructure($val));
            }
        } else {
            // Required options
            if (isset($data->partID)) {
                $ob->setContentId($data->partID);
            }

            $ob->setTransferEncoding(strtolower($data->encoding));
            $ob->setBytes($data->bytes);

            if ($ob->getType() == 'message/rfc822') {
                $ob->addPart($this->_parseStructure(reset($data->subParts)));
            }
        }

        return $ob;
    }
}
