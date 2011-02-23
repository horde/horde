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
     * Retrieves a list of folders on the server.
     *
     * @return array The list of folders.
     */
    public function listFolders()
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
     * @param string $folder    The name of the folder.
     * @param string $annotation The annotation to get.
     *
     * @return string The annotation value.
     */
    public function getAnnotation($folder, $annotation)
    {
        list($attr, $type) = $this->_getAnnotateMoreEntry($annotation);
        $result = $this->getBackend()->getAnnotation(
            $this->encodePath($folder), $attr, $type
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Setting annotation %s[%s] on folder %s failed. Error: %s"
                    ),
                    $attr,
                    $type,
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        return $result[$folder][$annotation];
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
        list($attr, $type) = $this->_getAnnotateMoreEntry($annotation);
        $this->getBackend()->setAnnotation(
            $this->encodePath($folder), array(array($attr, $type, $value))
        );
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Setting annotation %s[%s] on folder %s to %s failed. Error: %s"
                    ),
                    $attr,
                    $type,
                    $folder,
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
        foreach ($uids as $uid) {
            $structure = $this->getBackend()->tokenizeResponse(
                $this->getBackend()->fetchStructureString($folder, $uid, true)
            );
            if ($this->getBackend()->errornum != 0) {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf(
                        Horde_Kolab_Storage_Translation::t(
                            "Failed retrieving structure of message %s in folder %s. Error: %s"
                        ),
                        $uid,
                        $folder,
                        $this->getBackend()->error
                    )
                );
            }
            $ob = $this->_parseStructure($structure[0]);
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
        $resource = fopen('php://temp', 'r+');
        $this->getBackend()->handlePartBody($folder, $uid, true, $id, null, false, $resource);
        if ($this->getBackend()->errornum != 0) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed retrieving mime part %s of message %s in folder %s. Error: %s"
                    ),
                    $id,
                    $uid,
                    $folder,
                    $this->getBackend()->error
                )
            );
        }
        return $resource;
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
        return $this->getBackend()->append($folder, array(array('data' => $msg)));
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
        return $this->getBackend()->store($folder, array('add' => array('\\deleted'), 'ids' => $uids));
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
     * @param string $folder The folder to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function expunge($folder)
    {
        return $this->getBackend()->expunge($folder);
    }

    /**
     * Parse the output from imap_fetchstructure() into a MIME Part object.
     *
     * @param object $data  Data from imap_fetchstructure().
     *
     * @return Horde_Mime_Part  A MIME Part object.
     */
    /**
     * Recursively parse BODYSTRUCTURE data from a FETCH return (see
     * RFC 3501 [7.4.2]).
     *
     * @param array $data  The tokenized information from the server.
     *
     * @return array  The array of bodystructure information.
     */
    protected function _parseStructure($data)
    {
        $ob = new Horde_Mime_Part();

        // If index 0 is an array, this is a multipart part.
        if (is_array($data[0])) {
            // Keep going through array values until we find a non-array.
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                if (!is_array($data[$i])) {
                    break;
                }
                $ob->addPart($this->_parseStructure($data[$i]));
            }

            // The first string entry after an array entry gives us the
            // subpart type.
            $ob->setType('multipart/' . $data[$i]);

            // After the subtype is further extension information. This
            // information MAY not appear for BODYSTRUCTURE requests.

            // This is parameter information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                foreach ($this->_parseStructureParams($data[$i], 'content-type') as $key => $val) {
                    $ob->setContentTypeParameter($key, $val);
                }
            }

            // This is disposition information.
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ob->setDisposition($data[$i][0]);

                foreach ($this->_parseStructureParams($data[$i][1], 'content-disposition') as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }

            // This is language information. It is either a single value or
            // a list of values.
            if (isset($data[++$i])) {
                $ob->setLanguage($data[$i]);
            }

            // Ignore: location (RFC 2557)
            // There can be further information returned in the future, but
            // for now we are done.
        } else {
            $ob->setType($data[0] . '/' . $data[1]);

            foreach ($this->_parseStructureParams($data[2], 'content-type') as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }

            if ($data[3] !== null) {
                $ob->setContentId($data[3]);
            }

            if ($data[4] !== null) {
                $ob->setDescription(Horde_Mime::decode($data[4], 'UTF-8'));
            }

            if ($data[5] !== null) {
                $ob->setTransferEncoding($data[5]);
            }

            if ($data[6] !== null) {
                $ob->setBytes($data[6]);
            }

            // If the type is 'message/rfc822' or 'text/*', several extra
            // fields are included
            switch ($ob->getPrimaryType()) {
            case 'message':
                if ($ob->getSubType() == 'rfc822') {
                    // Ignore: envelope
                    $ob->addPart($this->_parseStructure($data[8]));
                    // Ignore: lines
                    $i = 10;
                } else {
                    $i = 7;
                }
                break;

            case 'text':
                // Ignore: lines
                $i = 8;
                break;

            default:
                $i = 7;
                break;
            }

            // After the subtype is further extension information. This
            // information MAY appear for BODYSTRUCTURE requests.

            // Ignore: MD5

            // This is disposition information
            if (isset($data[++$i]) && is_array($data[$i])) {
                $ob->setDisposition($data[$i][0]);

                foreach ($this->_parseStructureParams($data[$i][1], 'content-disposition') as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }

            // This is language information. It is either a single value or
            // a list of values.
            if (isset($data[++$i])) {
                $ob->setLanguage($data[$i]);
            }

            // Ignore: location (RFC 2557)
        }

        return $ob;
    }

    /**
     * Helper function to parse a parameters-like tokenized array.
     *
     * @param array $data   The tokenized data.
     * @param string $type  The header name.
     *
     * @return array  The parameter array.
     */
    protected function _parseStructureParams($data, $type)
    {
        $params = array();

        if (is_array($data)) {
            for ($i = 0, $cnt = count($data); $i < $cnt; ++$i) {
                $params[strtolower($data[$i])] = $data[++$i];
            }
        }

        $ret = Horde_Mime::decodeParam($type, $params);

        return $ret['params'];
    }
}
