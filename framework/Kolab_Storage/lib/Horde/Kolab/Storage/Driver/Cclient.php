<?php
/**
 * An cclient based Kolab storage driver.
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
 * An cclient based Kolab storage driver.
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
class Horde_Kolab_Storage_Driver_Cclient
extends Horde_Kolab_Storage_Driver_Base
{
    /**
     * The c-client code -> MIME type conversion table.
     *
     * @var array
     */
    protected $_mimeTypes = array(
        TYPETEXT => 'text',
        TYPEMULTIPART => 'multipart',
        TYPEMESSAGE => 'message',
        TYPEAPPLICATION => 'application',
        TYPEAUDIO => 'audio',
        TYPEIMAGE => 'image',
        TYPEVIDEO => 'video',
        TYPEMODEL => 'model',
        TYPEOTHER => 'other'
    );

    /**
     * The c-client code -> MIME encodings conversion table.
     *
     * @var array
     */
    protected $_mimeEncodings = array(
        ENC7BIT => '7bit',
        ENC8BIT => '8bit',
        ENCBINARY => 'binary',
        ENCBASE64 => 'base64',
        ENCQUOTEDPRINTABLE => 'quoted-printable',
        ENCOTHER => 'unknown'
    );

    /**
     * Server name.
     *
     * @var string
     */
    private $_host;

    /**
     * Basic IMAP connection string.
     *
     * @var string
     */
    private $_base_mbox;

    /**
     * The currently selected folder.
     *
     * @var string
     */
    private $_selected;

    /**
     * Create the backend driver.
     *
     * @return mixed The backend driver.
     */
    public function createBackend()
    {
        if (!function_exists('imap_open')) {
            throw new Horde_Kolab_Storage_Exception('The IMAP extension is not available!');
        }
        $result = @imap_open(
            $this->_getBaseMbox(),
            $this->getParam('username'),
            $this->getParam('password'),
            OP_HALFOPEN
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Connecting to server %s failed. Error: %s"
                    ),
                    $this->_getHost(),
                    imap_last_error()
                )
            );
        }
        return $result;
    }

    /**
     * Return the root folder of the current user.
     *
     * @return string The id of the user that opened the IMAP connection.
     */
    private function _getBaseMbox()
    {
        if (!isset($this->_base_mbox)) {
            $this->_base_mbox = '{' . $this->_getHost()
                . ':' . $this->getParam('port') . '/imap';
            $secure = $this->getParam('secure');
            if (!empty($secure)) {
                $this->_base_mbox .= '/' . $secure . '/novalidate-cert';
            } else {
                $this->_base_mbox .= '/notls';
            }
            $this->_base_mbox .= '}';
        }
        return $this->_base_mbox;
    }

    /**
     * Return the root folder of the current user.
     *
     * @return string The id of the user that opened the IMAP connection.
     */
    private function _getHost()
    {
        if (!isset($this->_host)) {
            $this->_host = $this->getParam('host');
            if (empty($this->_host)) {
                throw new Horde_Kolab_Storage_Exception(
                    Horde_Kolab_Storage_Translation::t(
                        "Missing \"host\" parameter!"
                    )
                );
            }
        }
        return $this->_host;
    }

    /**
     * Retrieves a list of folders on the server.
     *
     * @return array The list of folders.
     *
     * @throws Horde_Kolab_Storage_Exception In case listing the folders failed.
     */
    public function listFolders()
    {
        return $this->decodeList($this->_listFolders());
    }

    /**
     * Retrieves a UTF7-IMAP encoded list of folders on the server.
     *
     * @return array The list of folders.
     *
     * @throws Horde_Kolab_Storage_Exception In case listing the folders failed.
     */
    private function _listFolders()
    {
        $folders = array();

        $result = imap_list($this->getBackend(), $this->_getBaseMbox(), '*');
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Listing folders for %s failed. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    imap_last_error()
                )
            );
        }

        $root = $this->_getBaseMbox();
        $server_len = strlen($root);
        foreach ($result as $folder) {
            if (substr($folder, 0, $server_len) == $root) {
                $folders[] = substr($folder, $server_len);
            }
        }

        return $folders;
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
        $result = imap_createmailbox(
            $this->getBackend(),
            $this->_getBaseMbox() . $this->encodePath($folder)
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Creating folder %s%s failed. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $folder,
                    imap_last_error()
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
        $result = imap_deletemailbox(
            $this->getBackend(),
            $this->_getBaseMbox() . $this->encodePath($folder)
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Deleting folder %s%s failed. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $folder,
                    imap_last_error()
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
        $result = imap_renamemailbox(
            $this->getBackend(),
            $this->_getBaseMbox() . $this->encodePath($old),
            $this->_getBaseMbox() . $this->encodePath($new)
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Renaming folder %s%s to %s%s failed. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $old,
                    $this->_getBaseMbox(),
                    $new,
                    imap_last_error()
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
        @imap_getacl(
            $this->getBackend(),
            $this->_getBaseMbox()
        );
        if (imap_last_error()  == 'ACL not available on this IMAP server') {
            return false;
        } else {
            return true;
        }
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
        $result = imap_getacl($this->getBackend(), $this->encodePath($folder));
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed reading ACL on folder %s. Error: %s"
                    ),
                    $folder,
                    imap_last_error()
                )
            );
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
        if (!function_exists('imap_myrights')) {
            throw new Horde_Kolab_Storage_Exception('PHP does not support imap_myrights.');
        }

        $result = imap_myrights($this->getBackend(), $this->encodePath($folder));
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed reading user rights on folder %s. Error: %s"
                    ),
                    $folder,
                    imap_last_error()
                )
            );
        }
        return $result;
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
        $result = imap_setacl($this->getBackend(), $this->encodePath($folder), $user, $acl);
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed setting ACL on folder %s for user %s to %acl. Error: %s"
                    ),
                    $folder,
                    $user,
                    $acl,
                    imap_last_error()
                )
            );
        }
        return $result;
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
        $this->setAcl($folder, $user, '');
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
        if (!function_exists('imap_getannotation')) {
            throw new Horde_Kolab_Storage_Exception(
                'This driver is not supported by your variant of PHP. The function "imap_getannotation" is missing!'
            );
        }
        list($entry, $value) = $this->_getAnnotateMoreEntry($annotation);
        $list = array();
        foreach ($this->_listFolders() as $folder) {
            $result = imap_getannotation($this->getBackend(), $folder, $entry, $value);
            if (isset($result[$value])) {
                $list[$folder] = $result[$value];
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
        list($entry, $key) = $this->_getAnnotateMoreEntry($annotation);
        $result = imap_getannotation(
            $this->getBackend(), $this->encodePath($folder), $entry, $key
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Retrieving annotation %s[%s] on folder %s%s failed. Error: %s"
                    ),
                    $entry,
                    $key,
                    $this->_getBaseMbox(),
                    $folder,
                    imap_last_error()
                )
            );
        }
        return $result[$key];
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
        list($entry, $key) = $this->_getAnnotateMoreEntry($annotation);
        $result = imap_setannotation(
            $this->getBackend(), $this->encodePath($folder), $entry, $key, $value
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Setting annotation %s[%s] on folder %s%s to %s failed. Error: %s"
                    ),
                    $entry,
                    $key,
                    $this->_getBaseMbox(),
                    $folder,
                    $value,
                    imap_last_error()
                )
            );
        }
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
        $selection = $this->_getBaseMbox() . $this->encodePath($folder);
        if ($this->_selected != $selection) {
            $result = imap_reopen($this->getBackend(), $selection);
            if (!$result) {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf(
                        Horde_Kolab_Storage_Translation::t(
                            "Failed opening folder %s%s. Error: %s"
                        ),
                        $this->_getBaseMbox(),
                        $folder,
                        imap_last_error()
                    )
                );
            }
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
        $this->select($folder);
        $status = imap_status_current($this->getBackend(), SA_MESSAGES | SA_UIDVALIDITY | SA_UIDNEXT);
        if (!$status) {
            /**
             * @todo: The cclient method seems pretty much unable to detect
             * missing folders. It always returns "true"
             */
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed retrieving status information for folder %s%s. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $folder,
                    imap_last_error()
                )
            );
        }
        return array(
            'uidvalidity' => $status->uidvalidity,
            'uidnext' => $status->uidnext
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
        $uids = imap_search($this->getBackend(), 'UNDELETED', SE_UID);
        /**
         * @todo Error recognition? Nada... :(
         */
        if (!is_array($uids)) {
            $uids = array();
        }
        return $uids;
    }

    /**
     * Fetches the objects for the specified UIDs.
     *
     * @param string $folder The folder to access.
     *
     * @return array The parsed objects.
     */
    /**
     * Retrieves the messages for the given message ids.
     *
     * @param string $folder The folder to fetch the messages from.
     * @param array  $uids   The message UIDs.
     *
     * @return array An array of message structures parsed into Horde_Mime_Part
     *               instances.
     */
    public function fetchStructure($folder, $uids)
    {
        $result = array();

        $this->select($folder);
        foreach ($uids as $uid) {
            $structure = @imap_fetchstructure($this->getBackend(), $uid, FT_UID);
            if ($structure) {
                $ob = $this->_parseStructure($structure);
                $ob->buildMimeIds();
                $result[$uid]['structure'] = $ob;
            } else {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf(
                        Horde_Kolab_Storage_Translation::t(
                            "Failed fetching structure information for messages %s in folder %s%s. Error: %s"
                        ),
                        $this->_getBaseMbox(),
                        join(',', $uids),
                        $folder,
                        imap_last_error()
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
     * @param array  $uid    The message UID.
     * @param array  $id     The mime part ID.
     *
     * @return resource|string The body part, as a stream resource or string.
     */
    public function fetchBodypart($folder, $uid, $id)
    {
        $this->select($folder);

        $result = @imap_fetchbody($this->getBackend(), $uid, $id, FT_UID);
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed fetching body part %s for message %s in folder %s%s. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $id,
                    $uid,
                    $folder,
                    imap_last_error()
                )
            );
        }
        return $result;
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
        $result = @imap_append(
            $this->getBackend(), 
            $this->_getBaseMbox() . $this->encodePath($folder), 
            stream_get_contents($msg)
        );
        if (!$result) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "Failed appending new to folder %s%s. Error: %s"
                    ),
                    $this->_getBaseMbox(),
                    $folder,
                    imap_last_error()
                )
            );
        }
        return $result;
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
        return $this->_imap->store($folder, array('add' => array('\\deleted'), 'ids' => $uids));
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
     * @param string $folder The folder to append the message(s) to. Either
     *                        in UTF7-IMAP or UTF-8.
     *
     * @return mixed  True or a PEAR error in case of an error.
     */
    public function expunge($folder)
    {
        return $this->_imap->expunge($folder);
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

        $ob->setType($this->_mimeTypes[$data->type] . '/' . ($data->ifsubtype ? strtolower($data->subtype) : Horde_Mime_Part::UNKNOWN));

        // Optional for multipart-parts, required for all others
        if ($data->ifparameters) {
            $params = array();
            foreach ($data->parameters as $val) {
                $params[$val->attribute] = $val->value;
            }

            $params = Horde_Mime::decodeParam('content-type', $params, 'UTF-8');
            foreach ($params['params'] as $key => $val) {
                $ob->setContentTypeParameter($key, $val);
            }
        }

        // Optional entries. 'location' and 'language' not supported
        if ($data->ifdisposition) {
            $ob->setDisposition($data->disposition);
            if ($data->ifdparameters) {
                $dparams = array();
                foreach ($data->dparameters as $val) {
                    $dparams[$val->attribute] = $val->value;
                }

                $dparams = Horde_Mime::decodeParam('content-disposition', $dparams, 'UTF-8');
                foreach ($dparams['params'] as $key => $val) {
                    $ob->setDispositionParameter($key, $val);
                }
            }
        }

        if ($ob->getPrimaryType() == 'multipart') {
            // multipart/* specific entries
            foreach ($data->parts as $val) {
                $ob->addPart($this->_parseStructure($val));
            }
        } else {
            // Required options
            if ($data->ifid) {
                $ob->setContentId($data->id);
            }
            if ($data->ifdescription) {
                $ob->setDescription(Horde_Mime::decode($data->description, 'UTF-8'));
            }

            $ob->setTransferEncoding($this->_mimeEncodings[$data->encoding]);
            $ob->setBytes($data->bytes);

            if ($ob->getType() == 'message/rfc822') {
                $ob->addPart($this->_parseStructure(reset($data->parts)));
            }
        }

        return $ob;
    }
}
