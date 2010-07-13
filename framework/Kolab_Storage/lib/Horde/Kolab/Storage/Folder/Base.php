<?php
/**
 * @package Kolab_Storage
 */

/**
 * The Kolab_Folder class represents an IMAP folder on the Kolab
 * server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
     * The folder name.
     *
     * @var string
     */
    public $name;

    /**
     * A new folder name if the folder should be renamed on the next
     * save.
     *
     * @var string
     */
    var $new_name;

    /**
     * The driver for this folder.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The handler for the list of Kolab folders.
     *
     * @var Kolab_storage
     */
    var $_storage;

    /**
     * The type of this folder.
     *
     * @var string
     */
    var $_type;

    /**
     * The complete folder type annotation (type + default).
     *
     * @var string
     */
    var $_type_annotation;

    /**
     * The owner of this folder.
     *
     * @var string
     */
    var $_owner;

    /**
     * The pure folder.
     *
     * @var string
     */
    var $_subpath;

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
     * Is this a default folder?
     *
     * @var boolean
     */
    var $_default;

    /**
     * The title of this folder.
     *
     * @var string
     */
    var $_title;

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
    var $_data;

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
     * Creates a Kolab Folder representation.
     *
     * @param string                        $name      Name of the folder
     */
    function __construct($name = null)
    {
        $this->name       = $name;
        $this->__wakeup();
    }

    /**
     * Initializes the object.
     */
    function __wakeup()
    {
        if (!isset($this->_data)) {
            $this->_data = array();
        }

        foreach($this->_data as $data) {
            $data->setFolder($this);
        }

        if (isset($this->_perms)) {
            $this->_perms->setFolder($this);
        }
    }

    /**
     * Returns the properties that need to be serialized.
     *
     * @return array  List of serializable properties.
     */
    function __sleep()
    {
        $properties = get_object_vars($this);
        unset($properties['_storage']);
        unset($properties['_driver']);
        $properties = array_keys($properties);
        return $properties;
    }

    /**
     * Restore the object after a deserialization.
     *
     * @param Horde_Kolab_Storage        $storage    The handler for the list of
     *                                               folders.
     * @param Horde_Kolab_Storage_Driver $driver The storage driver.
     */
    function restore(
        Horde_Kolab_Storage &$storage,
        Horde_Kolab_Storage_Driver &$driver
    ) {
        $this->_storage = $storage;
        $this->_driver  = $driver;
    }

    /**
     * Retrieve the driver for this folder.
     *
     * @return Horde_Kolab_Storage_Driver The folder driver.
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * Get the permissions for this folder.
     *
     * @return Horde_Kolab_Storage_Folder_Permission The permission handler.
     */
    public function getPermission()
    {
        if ($this->_perms === null) {
            $this->_perms = new Horde_Kolab_Storage_Folder_Permission(
                $this->getName(),
                $this,
                $this->_driver->getGroupHandler()
            );
        }
        return $this->_perms;
    }

    /**
     * Sets the permissions on this folder.
     *
     * @param Horde_Kolab_Storage_Folder_Permission $perms  Permission object.
     * @param boolean                               $update Save the updated
     *                                                      information?
     *
     * @return NULL
     */
    public function setPermission(
        Horde_Kolab_Storage_Folder_Permission $perms,
        $update = true
    ) {
        $this->_perms = $perms;
        if ($update) {
            $this->save();
        }
    }


    /**
     * Return the name of the folder.
     *
     * @return string The name of the folder.
     */
    public function getName()
    {
        if (isset($this->name)) {
            return $this->name;
        }
        if (isset($this->new_name)) {
            return $this->new_name;
        }
    }

    /**
     * Set a new name for the folder. The new name will be realized
     * when saving the folder.
     *
     * @param string $name  The new folder name
     */
    function setName($name)
    {
        $this->new_name = $this->_driver->getNamespace()->setName($name);
    }

    /**
     * Set a new IMAP folder name for the folder. The new name will be
     * realized when saving the folder.
     *
     * @param string $name  The new folder name.
     */
    function setFolder($name)
    {
        $this->new_name = $name;
    }

    /**
     * Return the share ID of this folder.
     *
     * @return string The share ID of this folder.
     */
    function getShareId()
    {
        $current_user = $GLOBALS['registry']->getAuth();
        if ($this->isDefault() && $this->getOwner() == $current_user) {
            return $current_user;
        }
        return rawurlencode($this->name);
    }

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
        if (!isset($this->name)) {
            /* A new folder needs to be created */
            if (!isset($this->new_name)) {
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

            $result = $this->_driver->exists($this->new_name);
            if ($result) {
                throw new Horde_Kolab_Storage_Exception(sprintf("Unable to add %s: destination folder already exists",
                                                                $this->new_name),
                                                        Horde_Kolab_Storage_Exception::FOLDER_EXISTS);
            }

            $this->_driver->create($this->new_name);

            $this->name = $this->new_name;
            $this->new_name = null;

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

            if (isset($this->new_name)
                && $this->new_name != $this->name) {
                /** The folder needs to be renamed */
                $result = $this->_driver->exists($this->new_name);
                if ($result) {
                    throw new Horde_Kolab_Storage_Exception(sprintf(_("Unable to rename %s to %s: destination folder already exists"),
                                                                    $name, $new_name));
                }

                $result = $this->_driver->rename($this->name, $this->new_name);
                $this->_storage->removeFromCache($this);

                $this->name     = $this->new_name;
                $this->new_name = null;
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
        $this->_driver->delete($this->name);
        $this->_storage->removeFromCache($this);
        return true;
    }

    /**
     * Returns the owner of the folder.
     *
     * @return string|PEAR_Error  The owner of this folder.
     */
    public function getOwner()
    {
        if (!isset($this->_owner)) {
            $owner = $this->_driver->getNamespace()->getOwner($this->getName());
            /**
             * @todo: Reconsider if this handling should really be done here
             * rather than in a module nearer to the applications.
             */
            switch ($owner) {
            case Horde_Kolab_Storage_Driver_Namespace::PERSONAL:
                $this->_owner = $this->_driver->getAuth();
                break;
            case Horde_Kolab_Storage_Driver_Namespace::SHARED:
                $this->_owner = 'anonymous';
                break;
            default:
                list($prefix, $user) = explode(':', $owner, 2);
                if (strpos($user, '@') === false) {
                    $domain = strstr($this->_driver->getAuth(), '@');
                    if (!empty($domain)) {
                        $user .= $domain;
                    }
                }
                $this->_owner = $user;
                break;
            }
        }
        return $this->_owner;
    }

    /**
     * Returns the subpath of the folder.
     *
     * @param string $name Name of the folder that should be triggered.
     *
     * @return string|PEAR_Error  The subpath of this folder.
     *
     * @todo Is this is only needed by triggering? Can it be removed/moved?
     */
    public function getSubpath($name = null)
    {
        if (!empty($name)) {
            return $this->_driver->getNamespace()->getSubpath($name);
        }
        if (!isset($this->_subpath)) {
            $this->_subpath = $this->_driver->getNamespace()->getSubpath($this->getName());
        }
        return $this->_subpath;
    }

    /**
     * Returns a readable title for this folder.
     *
     * @return string  The folder title.
     */
    public function getTitle()
    {
        if (!isset($this->_title)) {
            $this->_title = $this->_driver->getNamespace()->getTitle($this->getName());
        }
        return $this->_title;
    }

    /**
     * The type of this folder.
     *
     * @return string|PEAR_Error  The folder type.
     */
    function getType()
    {
        if (!isset($this->_type)) {
            try {
                $type_annotation = $this->_getAnnotation(self::ANNOT_FOLDER_TYPE,
                                                         $this->name);
            } catch (Exception $e) {
                $this->_default = false;
                throw $e;
            }
            if (empty($type_annotation)) {
                $this->_default = false;
                $this->_type = '';
            } else {
                $type = explode('.', $type_annotation);
                $this->_default = (!empty($type[1]) && $type[1] == 'default');
                $this->_type = $type[0];
            }
            $this->_type_annotation = $type_annotation;
        }
        return $this->_type;
    }

    /**
     * Is this a default folder?
     *
     * @return boolean Boolean that indicates the default status.
     */
    function isDefault()
    {
        if (!isset($this->_default)) {
            /* This call also determines default status */
            $this->getType();
        }
        return $this->_default;
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
            $annotation = $this->_getAnnotation($entry, $this->name);
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
            $annotation = $this->_getAnnotation($entry, $this->name);
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
        if ($this->name === null) {
            return false;
        }
        try {
            return $this->_driver->exists($this->name);
        } catch (Horde_Imap_Client_Exception $e) {
            return false;
        }
    }

    /**
     * Returns whether the folder is accessible.
     *
     * @return boolean|PEAR_Error   True if the folder can be accessed.
     */
    function accessible()
    {
        try {
            return $this->_driver->select($this->name);
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
        $this->_driver->deleteMessages($this->name, $id);
        $this->_driver->expunge($this->name);
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
        $this->_driver->select($this->name);
        $this->_driver->moveMessage($this->name, $id, $folder);
        $this->_driver->expunge($this->name);
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
        $this->_driver->select($this->name);

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
            if (!in_array($id, $this->_driver->getUids($this->name))) {
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
                $part->setCharset($GLOBALS['registry']->getCharset());
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
            $part->setCharset($GLOBALS['registry']->getCharset());
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
            $this->_driver->deleteMessages($this->name, $id);
        }

        // store new email
        try {
            $result = $this->_driver->appendMessage($this->name, $msg);
        } catch (Horde_Kolab_Storage_Exception $e) {
            if ($id != null) {
                $this->_driver->undeleteMessages($id);
            }
        }

        // remove deleted object
        if ($id != null) {
            $this->_driver->expunge($this->name);
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
        $raw_headers = $this->_driver->getMessageHeader($this->name, $id);
        if (is_a($raw_headers, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Failed retrieving the message with ID %s. Original error: %s."),
                                            $id, $raw_headers->getMessage()));
        }

        $body = $this->_driver->getMessageBody($this->name, $id);
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
        $part->setContents(Horde_String::wrap($kolab_text, 76, "\r\n", $GLOBALS['registry']->getCharset()));
        $part->setCharset($GLOBALS['registry']->getCharset());

        $part->setTransferEncoding('quoted-printable');
        $mime_message->addPart($part);
        return $mime_message;
    }

    /**
     * Report the status of this folder.
     *
     * @return array|PEAR_Error An array listing the validity ID, the
     *                          next IMAP ID and an array of IMAP IDs.
     */
    function getStatus()
    {
        // Select the folder to update uidnext
        $this->_driver->select($this->name);

        $status = $this->_driver->status($this->name);
        $uids   = $this->_driver->getUids($this->name);
        return array($status['uidvalidity'], $status['uidnext'], $uids);
    }

    /**
     * Return the ACL of this folder.
     *
     * @return array An array with ACL.
     */
    public function getAcl()
    {
        if (!$this->exists()) {
            array($this->getDriver()->getAuth() => 'lrid');
        }
        return $this->getDriver()->getAcl($this);
    }

    /**
     * Set the ACL of this folder.
     *
     * @param $user The user for whom the ACL should be set.
     * @param $acl  The new ACL value.
     *
     * @return NULL
     */
    public function setAcl($user, $acl)
    {
        $this->getDriver()->setAcl(
            $this->getName(), $user, $acl
        );
    }

    /**
     * Delete the ACL for a user on this folder.
     *
     * @param $user The user for whom the ACL should be deleted.
     *
     * @return NULL
     */
    public function deleteAcl($user)
    {
        $this->getDriver()->deleteAcl(
            $this->getName(), $user
        );
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
            return $this->_driver->getAnnotation($key, $this->name);
        }

        if (!isset($this->_annotation_data)) {
            $this->_getAnnotationData();
        }
        $data = $this->_annotation_data->getObject('KOLAB_FOLDER_CONFIGURATION');
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Error retrieving annotation data on folder %s: %s',
                                      $this->name, $data->getMessage()), 'ERR');
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
            return $this->_driver->setAnnotation($key, $value, $this->name);
        }

        if (!isset($this->_annotation_data)) {
            $this->_getAnnotationData();
        }
        $data = $this->_annotation_data->getObject('KOLAB_FOLDER_CONFIGURATION');
        if (is_a($data, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Error retrieving annotation data on folder %s: %s',
                                      $this->name, $data->getMessage()), 'ERR');
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
