<?php
/**
 * Handles data objects in a Kolab storage folder.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The Kolab_Data class represents a data type in a Kolab storage
 * folder on the Kolab server.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Data_Old
{
    /**
     * The link to the parent folder object.
     *
     * @var Kolab_Folder
     */
    private $_folder;

    /**
     * The folder type.
     *
     * @var string
     */
    private $_type;

    /**
     * The driver for accessing the Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The factory for generating additional resources.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * The object type of the data.
     *
     * @var string
     */
    private $_object_type;

    /**
     * The version of the data.
     *
     * @var int
     */
    private $_data_version;

    /**
     * The data cache.
     *
     * @var Kolab_Cache
     */
    private $_cache;

    /**
     * The Id of this data object in the cache.
     *
     * @var string
     */
    private $_cache_key;

    /**
     * An addition to the cache key in case we are operating on
     * something other than the default type.
     *
     * @var string
     */
    private $_type_key;

    /**
     * Do we optimize for cyrus IMAPD?
     *
     * @var boolean
     */
    private $_cache_cyrus_optimize = true;

    /**
     * Creates a Kolab Folder Data representation.
     *
     * @param string $folder       Name/ID of the folder.
     * @param string $type         Type of the folder.
     * @param Horde_Kolab_Storage_Driver      $driver  The primary connection driver.
     * @param Horde_Kolab_Storage_Factory     $factory The factory.
     * @param string $object_type  Type of the objects we want to read.
     * @param int    $data_version Format version of the object data.
     */
    public function __construct($folder, $type, $driver, $factory, $object_type = null, $data_version = 1)
    {
        $this->_type = $type;
        if (!empty($object_type)) {
            $this->_object_type = $object_type;
        } else {
            $this->_object_type = $type;
        }
        $this->_data_version = $data_version;

        if ($this->_object_type != $this->_type) {
            $this->_type_key = '@' . $this->_object_type;
        } else {
            $this->_type_key = '';
        }
    }

    /**
     * Delete the specified message from this folder.
     *
     * @param string $object_uid Id of the message to be deleted.
     *
     * @return boolean|PEAR_Error True is successful, false if the
     *                            message does not exist.
     */
    public function delete($object_uid)
    {
        if (!$this->objectUidExists($object_uid)) {
            return false;
        }

        // Find the storage ID
        $id = $this->getStorageId($object_uid);
        if ($id === false) {
            return false;
        }

        $result = $this->_folder->deleteMessage($id);
        if (is_a($result, 'PEAR_Error')) {
            return $result;
        }

        unset($this->_cache->objects[$object_uid]);
        unset($this->_cache->uids[$id]);
        $this->_cache->save();
        return true;
    }

    /**
     * Delete all messages from the current folder.
     *
     * @return boolean|PEAR_Error True if successful.
     */
    public function deleteAll()
    {
        $this->_cache->load($this->_cache_key, $this->_data_version);

        if (empty($this->_cache->uids)) {
            return true;
        }
        foreach ($this->_cache->uids as $id => $object_uid) {
            $this->_folder->deleteMessage($id, false);

            unset($this->_cache->objects[$object_uid]);
            unset($this->_cache->uids[$id]);
        }
        $this->_cache->save();

        $result = $this->_folder->trigger();
        if (is_a($result, 'PEAR_Error')) {
            Horde::logMessage(sprintf('Failed triggering folder %s!',
                                      $this->_folder->name), 'ERR');
        }

        return true;
    }

    /**
     * Move the specified message from the current folder into a new
     * folder.
     *
     * @param string $object_uid ID of the message to be deleted.
     * @param string $new_share  ID of the target share.
     *
     * @return boolean|PEAR_Error True is successful, false if the
     *                            object does not exist.
     */
    public function move($object_uid, $new_share)
    {
        if (!$this->objectUidExists($object_uid)) {
            return false;
        }

        // Find the storage ID
        $id = $this->getStorageId($object_uid);
        if ($id === false) {
            return false;
        }

        $result = $this->_folder->moveMessageToShare($id, $new_share);

        unset($this->_cache->objects[$object_uid]);
        unset($this->_cache->uids[$id]);
        $this->_cache->save();
        return true;
    }

    /**
     * Save an object.
     *
     * @param array  $object        The array that holds the data object.
     * @param string $old_object_id The id of the object if it existed before.
     *
     * @return boolean True on success.
     *
     * @throws Horde_Kolab_Storage_Exception In case the given old object id
     *                                       is invalid or an error occured
     *                                       while saving the data.
     */
    public function save($object, $old_object_id = null)
    {
        // update existing kolab object
        if ($old_object_id != null) {
            // check if object really exists
            if (!$this->objectUidExists($old_object_id)) {
                throw new Horde_Kolab_Storage_Exception(sprintf(Horde_Kolab_Storage_Translation::t("Old object %s does not exist."),
                                                                $old_object_id));
            }

            // get the storage ID
            $id = $this->getStorageId($old_object_id);
            if ($id === false) {
                throw new Horde_Kolab_Storage_Exception(sprintf(Horde_Kolab_Storage_Translation::t("Old object %s does not map to a uid."),
                                                                $old_object_id));
            }

            $old_object = $this->getObject($old_object_id);
        } else {
            $id         = null;
            $old_object = null;
        }

        $this->_folder->saveObject($object, $this->_data_version,
                                   $this->_object_type, $id, $old_object);

        $this->synchronize($old_object_id);
        return true;
    }

    /**
     * Synchronize the data cache for the current folder.
     *
     * @param string $history_ignore Object uid that should not be
     *                               updated in the History
     *
     * @return NULL
     */
    public function synchronize($history_ignore = null)
    {
        $this->_cache->load($this->_cache_key, $this->_data_version);

        $result = $this->_folder->getStatus();

        list($validity, $nextid, $ids) = $result;

        $changes = $this->_folderChanged($validity, $nextid,
                                         array_keys($this->_cache->uids), $ids);
        if ($changes) {
            $modified = array();

            $recent_uids = array_diff($ids, array_keys($this->_cache->uids));

            $formats = $this->_folder->getFormats();

            $handler = Horde_Kolab_Format::factory('Xml', $this->_object_type,
                                                   $this->_data_version);

            $count = 0;
            foreach ($recent_uids as $id) {

                if ($this->_type == 'annotation' && $id != 1) {
                    continue;
                }

                try {
                    $mime = $this->_folder->parseMessage($id,
                                                         $handler->getMimeType(),
                                                         false);
                    $text = $mime[0];
                } catch (Horde_Kolab_Storage_Exception $e) {
                    Horde::logMessage($mime, 'WARN');
                    $text = false;
                }

                if ($text) {
                    $object = $handler->load($text);
                    if (is_a($object, 'PEAR_Error')) {
                        $this->_cache->ignore($id);
                        $object->addUserInfo('STORAGE ID: ' . $id);
                        Horde::logMessage($object, 'WARN');
                        continue;
                    }
                } else {
                    $object = false;
                }

                if ($object !== false) {
                    $message      = &$mime[2];
                    $handler_type = $handler->getMimeType();
                    foreach ($message->getParts() as $part) {
                        $name = $part->getName();
                        $type = $part->getType();
                        $dp   = $part->getDispositionParameter('x-kolab-type');
                        if (!empty($name) && $type != $handler_type
                            || (!empty($dp) && in_array($dp, $formats))) {
                            $object['_attachments'][$name]['type'] = $type;
                            $object['_attachments'][$name]['key']  = $this->_cache_key . '/' . $object['uid'] . ':' . $name;
                            //@todo: Check what to do with this call
                            //$part->transferDecodeContents();
                            $result = $this->_cache->storeAttachment($object['_attachments'][$name]['key'],
                                                                     $part->getContents());
                            if (is_a($result, 'PEAR_Error')) {
                                Horde::logMessage(sprintf('Failed storing attachment of object %s: %s',
                                                          $id,
                                                          $result->getMessage()), 'ERR');
                                $object = false;
                                break;
                            }
                        }
                    }
                }

                if ($object !== false) {
                    $this->_cache->store($id, $object['uid'], $object);
                    $mod_ts = time();
                    if (is_array($changes) && in_array($object['uid'], $changes)
                        && $object['uid'] != $history_ignore) {
                        $this->_updateHistory($object['uid'], $mod_ts, 'modify');
                        $modified[] = $object['uid'];
                    } else {
                        $this->_updateHistory($object['uid'], $mod_ts, 'add');
                    }
                } else {
                    $this->_cache->ignore($id);
                }

                // write out cache once in a while so if the browser times out
                // we don't have to start from the beginning.
                if ($count > 500) {
                    $count = 0;
                    $this->_cache->save();
                }
                $count++;
            }

            $this->_cache->save();

            if (is_array($changes)) {
                $deleted = array_diff($changes, $modified);
                foreach ($deleted as $deleted_oid) {
                    if ($deleted_oid != $history_ignore) {
                        $this->_updateHistory($deleted_oid, time(), 'delete');
                    }
                }
            }
        }
    }

    /**
     * Update the Horde history in case an element was modified
     * outside of Horde.
     *
     * @param string $object_uid Object uid that should be updated.
     * @param int    $mod_ts     Timestamp of the modification.
     * @param string $action     The action that was performed.
     *
     * @return NULL
     */
    private function _updateHistory($object_uid, $mod_ts, $action)
    {
        global $registry;

        if (!isset($registry)) {
            return;
        }

        $app = $registry->getApp();
        if (empty($app) || is_a($app, 'PEAR_Error')) {
            /**
             * Ignore the history if we are not in application
             * context.
             */
            return $app;
        }

        if (!class_exists('Horde_History')) {
            return;
        }

        /* Log the action on this item in the history log. */
        try {
            $GLOBALS['injector']->getInstance('Horde_History')
                ->log($app . ':' . $this->_folder->getShareId() . ':' . $object_uid,
                      array('action' => $action, 'ts' => $mod_ts),
                      true);
        } catch (Horde_Exception $e) {
        }
    }


    /**
     * Test if the storage ID exists.
     *
     * @param int $uid The storage ID.
     *
     * @return boolean True if the ID exists.
     */
    public function storageIdExists($uid)
    {
        $this->_cache->load($this->_cache_key, $this->_data_version);

        return array_key_exists($uid, $this->_cache->uids);
    }

    /**
     * Generate a unique object id.
     *
     * @return string  The unique id.
     */
    public function generateUID()
    {
        do {
            $key = md5(uniqid(mt_rand(), true));
        } while ($this->objectUidExists($key));

        return $key;
    }

    /**
     * Return the specified attachment.
     *
     * @param string $attachment_id The attachment id.
     *
     * @return string|PEAR_Error  The attachment data as a string.
     */
    public function getAttachment($attachment_id)
    {
        $this->_cache->load($this->_cache_key, $this->_data_version);

        return $this->_cache->loadAttachment($attachment_id);
    }

    /**
     * Retrieve all objects in the current folder as an array.
     *
     * @return array  The object data array.
     */
    public function getObjectArray()
    {
        $this->_cache->load($this->_cache_key, $this->_data_version);

        return $this->_cache->objects;
    }
}
