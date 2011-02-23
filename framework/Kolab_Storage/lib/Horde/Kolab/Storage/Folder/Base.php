<?php
/**
 * The Kolab_Folder class represents an single folder in the Kolab
 * backend.
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
 * The Kolab_Folder class represents an single folder in the Kolab
 * backend.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @package Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Base
implements Horde_Kolab_Storage_Folder
{
    /**
     * The handler for the list of folders.
     *
     * @var Horde_Kolab_Storage_List
     */
    private $_list;

    /**
     * The folder path.
     *
     * @var string
     */
    private $_path;

    /**
     * Additional folder information.
     *
     * @var array
     */
    private $_data;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_List $list The handler for the list of
     *                                       folders.
     * @param string                   $path Path of the folder.
     */
    public function __construct(
        Horde_Kolab_Storage_List $list, $path
    ) {
        $this->_list = $list;
        $this->_path = $path;
    }

    /**
     * Fetch the data array.
     *
     * @return NULL
     */
    private function _init()
    {
        if ($this->_data === null) {
            $this->_data = $this->_list->getQuery()->folderData($this->_path);
        }
    }

    /**
     * Fetch a data value.
     *
     * @param string $key The name of the data value to fetch.
     *
     * @return mixed The data value
     */
    public function get($key)
    {
        $this->_init();
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        throw new Horde_Kolab_Storage_Exception(
            sprintf('No "%s" information available!', $key)
        );
    }

    /**
     * Return the storage path of the folder.
     *
     * @return string The storage path of the folder.
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * Return the namespace of the folder.
     *
     * @return string The namespace of the folder.
     */
    public function getNamespace()
    {
        return $this->get('namespace');
    }

    /**
     * Returns a readable title for this folder.
     *
     * @return string  The folder title.
     */
    public function getTitle()
    {
        return $this->get('name');
    }

    /**
     * Returns the owner of the folder.
     *
     * @return string The owner of this folder.
     */
    public function getOwner()
    {
        return $this->get('owner');
    }

    /**
     * Returns the folder path without namespace components.
     *
     * @return string The subpath of this folder.
     */
    public function getSubpath()
    {
        return $this->get('subpath');
    }

    /**
     * Returns the folder parent.
     *
     * @return string The parent of this folder.
     */
    public function getParent()
    {
        return $this->get('parent');
    }

    /**
     * Is this a default folder?
     *
     * @return boolean Boolean that indicates the default status.
     */
    public function isDefault()
    {
        return $this->get('default');
    }

    /**
     * The type of this folder.
     *
     * @return string The folder type.
     */
    function getType()
    {
        return $this->get('type');
    }













    /**
     * The root of the Kolab annotation hierarchy, used on the various IMAP
     * folder that are used by Kolab clients.
     */
    const ANNOT_ROOT = '/shared/vendor/kolab/';

    /**
     * The annotation, as defined by the Kolab format spec, that is used to store
     * information about what groupware format the folder contains.
     */
    const ANNOT_FOLDER_TYPE = '/shared/vendor/kolab/folder-type';

    /**
     * Horde-specific annotations on the imap folder have this prefix.
     */
    const ANNOT_SHARE_ATTR = '/shared/vendor/horde/share-';

    /**
     * Kolab specific free/busy relevance
     */
    const FBRELEVANCE_ADMINS  = 0;
    const FBRELEVANCE_READERS = 1;
    const FBRELEVANCE_NOBODY  = 2;

    /**
     * Additional Horde folder attributes.
     *
     * @var array
     */
    var $_attributes;

    /**
     * Additional Kolab folder attributes.
     *
     * @var array
     */
    var $_kolab_attributes;

    /**
     * The permission handler for the folder.
     *
     * @var Horde_Permission_Kolab
     */
    var $_perms;

    /**
     * Links to the data handlers for this folder.
     *
     * @var array
     */
    //    var $_data;

    /**
     * Links to the annotation data handlers for this folder.
     *
     * @var array
     */
    var $_annotation_data;

    /**
     * Indicate that the folder data has been modified from the
     * outside and all Data handlers need to synchronize.
     *
     * @var boolean
     */
    var $tainted = false;










    /**
     * Saves the folder.
     *
     * @param array $attributes An array of folder attributes. You can
     *                          set any attribute but there are a few
     *                          special ones like 'type', 'default',
     *                          'owner' and 'desc'.
     *
     * @return NULL
     */
    public function save($attributes = null)
    {
        if (!isset($this->_path)) {
            /* A new folder needs to be created */
            if (!isset($this->_new_path)) {
                throw new Horde_Kolab_Storage_Exception('Cannot create this folder! The name has not yet been set.',
                                                        Horde_Kolab_Storage_Exception::FOLDER_NAME_UNSET);
            }

            if (isset($attributes['type'])) {
                $this->_type = $attributes['type'];
                unset($attributes['type']);
            } else {
                $this->_type = 'mail';
            }

            if (isset($attributes['default'])) {
                $this->_default = $attributes['default'];
                unset($attributes['default']);
            } else {
                $this->_default = false;
            }

            $result = $this->_driver->exists($this->_new_path);
            if ($result) {
                throw new Horde_Kolab_Storage_Exception(sprintf("Unable to add %s: destination folder already exists",
                                                                $this->_new_path),
                                                        Horde_Kolab_Storage_Exception::FOLDER_EXISTS);
            }

            $this->_driver->create($this->_new_path);

            $this->_path = $this->_new_path;
            $this->_new_path = null;

            /* Initialize the new folder to default permissions */
            if (empty($this->_perms)) {
                $this->getPermission();
            }
        } else {

            $type = $this->getType();

            if (isset($attributes['type'])) {
                if ($attributes['type'] != $type) {
                    Horde::logMessage(sprintf('Cannot modify the type of a folder from %s to %s!',
                                              $type, $attributes['type']), 'ERR');
                }
                unset($attributes['type']);
            }

            if (isset($attributes['default'])) {
                $this->_default = $attributes['default'];
                unset($attributes['default']);
            } else {
                $this->_default = $this->isDefault();
            }

            if (isset($this->_new_path)
                && $this->_new_path != $this->_path) {
                /** The folder needs to be renamed */
                $result = $this->_driver->exists($this->_new_path);
                if ($result) {
                    throw new Horde_Kolab_Storage_Exception(sprintf(_("Unable to rename %s to %s: destination folder already exists"),
                                                                    $name, $new_name));
                }

                $result = $this->_driver->rename($this->_path, $this->_new_path);
                $this->_storage->removeFromCache($this);

                $this->_path     = $this->_new_path;
                $this->_new_path = null;
                $this->_title   = null;
                $this->_owner   = null;
            }
        }

        if (isset($attributes['owner'])) {
            if ($attributes['owner'] != $this->getOwner()) {
                Horde::logMessage(sprintf('Cannot modify the owner of a folder from %s to %s!',
                                          $this->getOwner(), $attributes['owner']), 'ERR');
            }
            unset($attributes['owner']);
        }

        /** Handle the folder type */
        $folder_type = $this->_type . ($this->_default ? '.default' : '');
        if ($this->_type_annotation != $folder_type) {
            try {
                $result = $this->_setAnnotation(self::ANNOT_FOLDER_TYPE, $folder_type);
            } catch (Exception $e) {
                $this->_type = null;
                $this->_default = false;
                $this->_type_annotation = null;
                throw $e;
            }
        }

        if (!empty($attributes)) {
            if (!is_array($attributes)) {
                $attributes = array($attributes);
            }
            foreach ($attributes as $key => $value) {
                if ($key == 'params') {
                    $params = unserialize($value);
                    if (isset($params['xfbaccess'])) {
                        $result = $this->setXfbAccess($params['xfbaccess']);
                        if (is_a($result, 'PEAR_Error')) {
                            return $result;
                        }
                    }
                    if (isset($params['fbrelevance'])) {
                        $result = $this->setFbrelevance($params['fbrelevance']);
                        if (is_a($result, 'PEAR_Error')) {
                            return $result;
                        }
                    }
                }

                // setAnnotation apparently does not suppoort UTF-8 nor any special characters
                $store = base64_encode($value);
                if ($key == 'desc') {
                    $entry = '/shared/comment';
                } else {
                    $entry = self::ANNOT_SHARE_ATTR . $key;
                }
                $result = $this->_setAnnotation($entry, $store);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
            }
            $this->_attributes = $attributes;
        }

        /** Now save the folder permissions */
        if (isset($this->_perms)) {
            $this->_perms->save();
            $this->_perms = null;
        }

        $this->_storage->addToCache($this);

        return true;
    }

    /**
     * Delete this folder.
     *
     * @return boolean|PEAR_Error True if the operation succeeded.
     */
    function delete()
    {
        $this->_driver->delete($this->_path);
        $this->_storage->removeFromCache($this);
        return true;
    }

    /**
     * Returns one of the attributes of the folder, or an empty string
     * if it isn't defined.
     *
     * @param string $attribute  The attribute to retrieve.
     *
     * @return mixed The value of the attribute, an empty string or an
     *               error.
     */
    function getAttribute($attribute)
    {
        if (!isset($this->_attributes[$attribute])) {
            if ($attribute == 'desc') {
                $entry = '/comment';
            } else {
                $entry = self::ANNOT_SHARE_ATTR . $attribute;
            }
            $annotation = $this->_getAnnotation($entry, $this->_path);
            if (is_a($annotation, 'PEAR_Error')) {
                return $annotation;
            }
            if (empty($annotation)) {
                $this->_attributes[$attribute] = '';
            } else {
                $this->_attributes[$attribute] = base64_decode($annotation);
            }
        }
        return $this->_attributes[$attribute];
    }

    /**
     * Returns one of the Kolab attributes of the folder, or an empty
     * string if it isn't defined.
     *
     * @param string $attribute  The attribute to retrieve.
     *
     * @return mixed The value of the attribute, an empty string or an
     *               error.
     */
    function getKolabAttribute($attribute)
    {
        if (!isset($this->_kolab_attributes[$attribute])) {
            $entry = KOLAB_ANNOT_ROOT . $attribute;
            $annotation = $this->_getAnnotation($entry, $this->_path);
            if (is_a($annotation, 'PEAR_Error')) {
                return $annotation;
            }
            if (empty($annotation)) {
                $this->_kolab_attributes[$attribute] = '';
            } else {
                $this->_kolab_attributes[$attribute] = $annotation;
            }
        }
        return $this->_kolab_attributes[$attribute];
    }


    /**
     * Returns whether the folder exists.
     *
     * @return boolean|PEAR_Error  True if the folder exists.
     */
    function exists()
    {
        if ($this->_path === null) {
            return false;
        }
        try {
            return $this->_driver->exists($this->_path);
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }
    }

    /**
     * Retrieve a handler for the data in this folder.
     *
     * @param Kolab_List $list  The handler for the list of folders.
     *
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    public function getData($object_type = null, $data_version = 1)
    {
        if (empty($object_type)) {
            $object_type = $this->getType();
            if (is_a($object_type, 'PEAR_Error')) {
                return $object_type;
            }
        }

        if ($this->tainted) {
            foreach ($this->_data as $data) {
                $data->synchronize();
            }
            $this->tainted = false;
        }

        $key = $object_type . '|' . $data_version;
        if (!isset($this->_data[$key])) {
            if ($object_type != 'annotation') {
                $type = $this->getType();
            } else {
                $type = 'annotation';
            }
            $data = new Horde_Kolab_Storage_Data($type, $object_type, $data_version);
            $data->setFolder($this);
            $data->setCache($this->_storage->getDataCache());
            $data->synchronize();
            $this->_data[$key] = &$data;
        }
        return $this->_data[$key];
    }

    /**
     * Delete the specified message from this folder.
     *
     * @param  string  $id      IMAP id of the message to be deleted.
     * @param  boolean $trigger Should the folder be triggered?
     *
     * @return NULL
     */
    public function deleteMessage($id, $trigger = true)
    {
        // Select folder
        $this->_driver->deleteMessages($this->_path, $id);
        $this->_driver->expunge($this->_path);
    }

    /**
     * Move the specified message to the specified folder.
     *
     * @param string $id     IMAP id of the message to be moved.
     * @param string $folder Name of the receiving folder.
     *
     * @return boolean True if successful.
     */
    public function moveMessage($id, $folder)
    {
        $this->_driver->select($this->_path);
        $this->_driver->moveMessage($this->_path, $id, $folder);
        $this->_driver->expunge($this->_path);
    }

    /**
     * Move the specified message to the specified share.
     *
     * @param string $id    IMAP id of the message to be moved.
     * @param string $share Name of the receiving share.
     *
     * @return NULL
     */
    public function moveMessageToShare($id, $share)
    {
        $folder = $this->_storage->getByShare($share, $this->getType());
        $folder->tainted = true;

        $success = $this->moveMessage($id, $folder->name);
    }

    /**
     * Retrieve the supported formats.
     *
     * @return array The names of the supported formats.
     */
    function getFormats()
    {
        global $conf;

        if (empty($conf['kolab']['misc']['formats'])) {
            $formats = array('XML');
        } else {
            $formats = $conf['kolab']['misc']['formats'];
        }
        if (!is_array($formats)) {
            $formats = array($formats);
        }
        if (!in_array('XML', $formats)) {
            $formats[] = 'XML';
        }
        return $formats;
    }

    /**
     * Save an object in this folder.
     *
     * @param array  $object       The array that holds the data of the object.
     * @param int    $data_version The format handler version.
     * @param string $object_type  The type of the kolab object.
     * @param string $id           The IMAP id of the old object if it
     *                             existed before
     * @param array  $old_object   The array that holds the current data of the
     *                             object.
     *
     * @return boolean True on success.
     */
    public function saveObject(&$object, $data_version, $object_type, $id = null,
                        &$old_object = null)
    {
        // Select folder
        $this->_driver->select($this->_path);

        $new_headers = new Horde_Mime_Headers();
        $new_headers->setEOL("\r\n");

        $formats = $this->getFormats();

        $handlers = array();
        foreach ($formats as $type) {
            $handlers[$type] = &Horde_Kolab_Format::factory($type, $object_type,
                                                            $data_version);
            if (is_a($handlers[$type], 'PEAR_Error')) {
                if ($type == 'XML') {
                    return $handlers[$type];
                }
                Horde::logMessage(sprintf('Loading format handler "%s" failed: %s',
                                          $type, $handlers[$type]->getMessage()), 'ERR');
                continue;
            }
        }

        if ($id != null) {
            /** Update an existing kolab object */
            if (!in_array($id, $this->_driver->getUids($this->_path))) {
                return PEAR::raiseError(sprintf(_("The message with ID %s does not exist. This probably means that the Kolab object has been modified by somebody else while you were editing it. Your edits have been lost."),
                                                $id));
            }

            /** Parse email and load Kolab format structure */
            $result = $this->parseMessage($id, $handlers['XML']->getMimeType(),
                                          true, $formats);
            if (is_a($result, 'PEAR_Error')) {
                return $result;
            }
            list($old_message, $part_ids, $mime_message, $mime_headers) = $result;
            if (is_a($old_message, 'PEAR_Error')) {
                return $old_message;
            }

            if (isset($object['_attachments']) && isset($old_object['_attachments'])) {
                $attachments = array_keys($object['_attachments']);
                foreach (array_keys($old_object['_attachments']) as $attachment) {
                    if (!in_array($attachment, $attachments)) {
                        foreach ($mime_message->getParts() as $part) {
                            if ($part->getName() === $attachment) {
                                foreach (array_keys($mime_message->_parts) as $key) {
                                    if ($mime_message->_parts[$key]->getMimeId() == $part->getMimeId()) {
                                        unset($mime_message->_parts[$key]);
                                        break;
                                    }
                                }
                                $mime_message->_generateIdMap($mime_message->_parts);
                            }
                        }
                    }
                }
            }
            $object = array_merge($old_object, $object);

            if (isset($attachments)) {
                foreach ($mime_message->getParts() as $part) {
                    $name = $part->getName();
                    foreach ($attachments as $attachment) {
                        if ($name === $attachment) {
                            $object['_attachments'][$attachment]['id'] = $part->getMimeId();
                        }
                    }
                }
            }

            /** Copy email header */
            if (!empty($mime_headers) && !$mime_headers === false) {
                foreach ($mime_headers as $header => $value) {
                    $new_headers->addheader($header, $value);
                }
            }
        } else {
            $mime_message = $this->_prepareNewMessage($new_headers);
            $mime_part_id = false;
        }

        if (isset($object['_attachments'])) {
            $attachments = array_keys($object['_attachments']);
            foreach ($attachments as $attachment) {
                $data = $object['_attachments'][$attachment];

                if (!isset($data['content']) && !isset($data['path'])) {
                    /**
                     * There no new content and no new path. Do not rewrite the
                     * attachment.
                     */
                    continue;
                }

                $part = new Horde_Mime_Part();
                $part->setType(isset($data['type']) ? $data['type'] : null);
                $part->setContents(isset($data['content']) ? $data['content'] : file_get_contents($data['path']));
                $part->setCharset('UTF-8');
                $part->setTransferEncoding('quoted-printable');
                $part->setDisposition('attachment');
                $part->setName($attachment);

                if (!isset($data['id'])) {
                    $mime_message->addPart($part);
                } else {
                    $mime_message->alterPart($data['id'], $part);
                }
            }
        }

        foreach ($formats as $type) {
            $new_content = $handlers[$type]->save($object);
            if (is_a($new_content, 'PEAR_Error')) {
                return $new_content;
            }

            /** Update mime part */
            $part = new Horde_Mime_Part();
            $part->setType($handlers[$type]->getMimeType());
            $part->setContents($new_content);
            $part->setCharset('UTF-8');
            $part->setTransferEncoding('quoted-printable');
            $part->setDisposition($handlers[$type]->getDisposition());
            $part->setDispositionParameter('x-kolab-type', $type);
            $part->setName($handlers[$type]->getName());

            if (!isset($part_ids) || $part_ids[$type] === false) {
                $mime_message->addPart($part);
            } else {
                $mime_message->alterPart($part_ids[$type], $part);
            }
        }

        // Update email headers
        $new_headers->addHeader('From', $this->_driver->getAuth());
        $new_headers->addHeader('To', $this->_driver->getAuth());
        $new_headers->addHeader('Date', date('r'));
        $new_headers->addHeader('X-Kolab-Type', $handlers['XML']->getMimeType());
        $new_headers->addHeader('Subject', $object['uid']);
        $new_headers->addHeader('User-Agent', 'Horde::Kolab::Storage v0.2');
        $new_headers->addHeader('MIME-Version', '1.0');
        $mime_message->addMimeHeaders(array('headers' => $new_headers));

        $msg = $new_headers->toString() . $mime_message->toString(array('canonical' => true,
                                                                        'headers' => false));

        // delete old email?
        if ($id != null) {
            $this->_driver->deleteMessages($this->_path, $id);
        }

        // store new email
        try {
            $result = $this->_driver->appendMessage($this->_path, $msg);
        } catch (Horde_Kolab_Storage_Exception $e) {
            if ($id != null) {
                $this->_driver->undeleteMessages($id);
            }
        }

        // remove deleted object
        if ($id != null) {
            $this->_driver->expunge($this->_path);
        }
    }

    /**
     * Get an IMAP message and retrieve the Kolab Format object.
     *
     * @param int     $id             The message to retrieve.
     * @param string  $mime_type      The mime type of the part to retrieve.
     * @param boolean $parse_headers  Should the heades be Mime parsed?
     * @param array   $formats        The list of possible format parts.
     *
     * @return array|PEAR_Error An array that list the Kolab XML
     *                          object text, the mime ID of the part
     *                          with the XML object, the Mime parsed
     *                          message and the Mime parsed headers if
     *                          requested.
     */
    function parseMessage($id, $mime_type, $parse_headers = true,
                          $formats = array('XML'))
    {
        $raw_headers = $this->_driver->getMessageHeader($this->_path, $id);
        if (is_a($raw_headers, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Failed retrieving the message with ID %s. Original error: %s."),
                                            $id, $raw_headers->getMessage()));
        }

        $body = $this->_driver->getMessageBody($this->_path, $id);
        if (is_a($body, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Failed retrieving the message with ID %s. Original error: %s."),
                                            $id, $body->getMessage()));
        }

        //@todo: not setting "forcemime" means the subparts get checked too. Seems incorrect.
        $mime_message = Horde_Mime_Part::parseMessage($raw_headers . "\r" . $body, array('forcemime' => true));
        $parts = $mime_message->contentTypeMap();

        $mime_headers = false;
        $xml = false;

        // Read in a Kolab event object, if one exists
        $part_ids['XML'] = array_search($mime_type, $parts);
        if ($part_ids['XML'] !== false) {
            if ($parse_headers) {
                $mime_headers = Horde_Mime_Headers::parseHeaders($raw_headers);
                $mime_headers->setEOL("\r\n");
            }

            $part = $mime_message->getPart($part_ids['XML']);
            //@todo: Check what happened to this call
            //$part->transferDecodeContents();
            $xml = $part->getContents();
        }

        $alternate_formats = array_diff(array('XML'), $formats);
        if (!empty($alternate_formats)) {
            foreach ($alternate_formats as $type) {
                $part_ids[$type] = false;
            }
            foreach ($mime_message->getParts() as $part) {
                $params = $part->getDispositionParameters();
                foreach ($alternate_formats as $type) {
                    if (isset($params['x-kolab-format'])
                        && $params['x-kolab-format'] == $type) {
                        $part_ids[$type] = $part->getMimeId();
                    }
                }
            }
        }

        $result = array($xml, $part_ids, $mime_message, $mime_headers);
        return $result;
    }

    /**
     * Prepares a new kolab Groupeware message.
     *
     * @return string The Mime message
     */
    function _prepareNewMessage()
    {
        $mime_message = new Horde_Mime_Part();
        $mime_message->setName('Kolab Groupware Data');
        $mime_message->setType('multipart/mixed');
        $kolab_text = sprintf(_("This is a Kolab Groupware object. To view this object you will need an email client that understands the Kolab Groupware format. For a list of such email clients please visit %s"),
                              'http://www.kolab.org/kolab2-clients.html');
        $part = new Horde_Mime_Part();
        $part->setType('text/plain');
        $part->setName('Kolab Groupware Information');
        $part->setContents(Horde_String::wrap($kolab_text, 76, "\r\n"));
        $part->setCharset('UTF-8');

        $part->setTransferEncoding('quoted-printable');
        $mime_message->addPart($part);
        return $mime_message;
    }

    /**
     * Get annotation values on IMAP servers that do not support
     * METADATA.
     *
     * @return array|PEAR_Error  The anotations of this folder.
     */
    function _getAnnotationData()
    {
        $this->_annotation_data = $this->getData('annotation');
    }


    /**
     * Get an annotation value of this folder.
     *
     * @param $key The key of the annotation to retrieve.
     *
     * @return string|PEAR_Error  The anotation value.
     */
    function _getAnnotation($key)
    {
        global $conf;

        if (empty($conf['kolab']['imap']['no_annotations'])) {
            return $this->_driver->getAnnotation($key, $this->_path);
        }

        if (!isset($this->_annotation_data)) {
            $this->_getAnnotationData();
        }
        $data = $this->_annotation_data->getObject('KOLAB_FOLDER_CONFIGURATION');
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Error retrieving annotation data on folder %s: %s',
                                      $this->_path, $data->getMessage()), 'ERR');
            return '';
        }
        if (isset($data[$key])) {
            return $data[$key];
        } else {
            return '';
        }
    }

    /**
     * Set an annotation value of this folder.
     *
     * @param $key   The key of the annotation to change.
     * @param $value The new value.
     *
     * @return boolean|PEAR_Error  True on success.
     */
    function _setAnnotation($key, $value)
    {
        if (empty($conf['kolab']['imap']['no_annotations'])) {
            return $this->_driver->setAnnotation($key, $value, $this->_path);
        }

        if (!isset($this->_annotation_data)) {
            $this->_getAnnotationData();
        }
        $data = $this->_annotation_data->getObject('KOLAB_FOLDER_CONFIGURATION');
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Error retrieving annotation data on folder %s: %s',
                                      $this->_path, $data->getMessage()), 'ERR');
            $data = array();
            $uid = null;
        } else {
            $uid = 'KOLAB_FOLDER_CONFIGURATION';
        }
        $data[$key] = $value;
        $data['uid'] = 'KOLAB_FOLDER_CONFIGURATION';
        return $this->_annotation_data->save($data, $uid);
    }



    /**
     * Get the free/busy relevance for this folder
     *
     * @return int  Value containing the FB_RELEVANCE.
     */
    function getFbrelevance()
    {
        $result = $this->getKolabAttribute('incidences-for');
        if (is_a($result, 'PEAR_Error') || empty($result)) {
            return KOLAB_FBRELEVANCE_ADMINS;
        }
        switch ($result) {
        case 'admins':
            return KOLAB_FBRELEVANCE_ADMINS;
        case 'readers':
            return KOLAB_FBRELEVANCE_READERS;
        case 'nobody':
            return KOLAB_FBRELEVANCE_NOBODY;
        default:
            return KOLAB_FBRELEVANCE_ADMINS;
        }
    }

    /**
     * Set the free/busy relevance for this folder
     *
     * @param int $relevance Value containing the FB_RELEVANCE
     *
     * @return mixed  True on success or a PEAR_Error.
     */
    function setFbrelevance($relevance)
    {
        switch ($relevance) {
        case KOLAB_FBRELEVANCE_ADMINS:
            $value = 'admins';
            break;
        case KOLAB_FBRELEVANCE_READERS:
            $value = 'readers';
            break;
        case KOLAB_FBRELEVANCE_NOBODY:
            $value = 'nobody';
            break;
        default:
            $value = 'admins';
        }

        return $this->_setAnnotation(KOLAB_ANNOT_ROOT . 'incidences-for',
                                     $value);
    }

    /**
     * Get the extended free/busy access settings for this folder
     *
     * @return array  Array containing the users with access to the
     *                extended information.
     */
    function getXfbaccess()
    {
        $result = $this->getKolabAttribute('pxfb-readable-for');
        if (is_a($result, 'PEAR_Error') || empty($result)) {
            return array();
        }
        return explode(' ', $result);
    }

    /**
     * Set the extended free/busy access settings for this folder
     *
     * @param array $access  Array containing the users with access to the
     *                      extended information.
     *
     * @return mixed  True on success or a PEAR_Error.
     */
    function setXfbaccess($access)
    {
        $value = join(' ', $access);
        return $this->_setAnnotation(KOLAB_ANNOT_ROOT . 'pxfb-readable-for',
                                     $value);
    }
}
