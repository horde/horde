<?php
/** Kolab support class. */
require_once 'Horde/Kolab.php';

/**
 * Horde Turba driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Kolab extends Turba_Driver
{
    /**
     * Our Kolab server connection.
     *
     * @var Kolab
     */
    protected $_kolab = null;

    /**
     * The wrapper to decide between the Kolab implementation
     *
     * @var Turba_Driver_kolab_Wrapper
     */
    protected $_wrapper = null;

    protected $_capabilities = array(
        'delete_addressbook' => true,
        'delete_all' => true,
    );

    /**
     * Attempts to open a Kolab Groupware folder.
     */
    public function __construct($name = '', $params = array())
    {
        parent::__construct($name, $params);
        $this->_kolab = new Kolab();
        $wrapper = empty($this->_kolab->version)
            ? 'Turba_Driver_Kolab_Wrapper_old'
            : 'Turba_Driver_Kolab_Wrapper_new';

        $this->_wrapper = new $wrapper($this->_name, $this->_kolab);
    }

    /**
     * Searches the Kolab message store with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty
     * array, all records will be returned.
     *
     * @param $criteria      Array containing the search criteria.
     * @param $fields        List of fields to return.
     *
     * @return               Hash containing the search results.
     */
    protected function _search($criteria, $fields, $blobFields = array())
    {
        return $this->_wrapper->_search($criteria, $fields);
    }

    /**
     * Read the given data from the Kolab message store and returns the
     * results.
     *
     * @param string $key    The primary key field to use.
     * @param mixed $ids     The ids of the contacts to load.
     * @param string $owner  Only return contacts owned by this user.
     * @param array $fields  List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    protected function _read($key, $ids, $owner, $fields)
    {
        return $this->_wrapper->_read($key, $ids, $fields);
    }

    /**
     * Adds the specified object to the Kolab message store.
     */
    protected function _add($attributes)
    {
        return $this->_wrapper->_add($attributes);
    }

    protected function _canAdd()
    {
        return true;
    }

    /**
     * Removes the specified object from the Kolab message store.
     */
    protected function _delete($object_key, $object_id)
    {
        return $this->_wrapper->_delete($object_key, $object_id);
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @return boolean  True if the operation worked.
     */
    protected function _deleteAll($sourceName = null)
    {
        return $this->_wrapper->_deleteAll($sourceName);
    }

    /**
     * Updates an existing object in the Kolab message store.
     *
     * @return string  The object id, possibly updated.
     */
    protected function _save($object)
    {
        list($object_key, $object_id) = each($this->toDriverKeys(array('__key' => $object->getValue('__key'))));
        $attributes = $this->toDriverKeys($object->getAttributes());

        return $this->_wrapper->_save($object_key, $object_id, $attributes);
    }

    /**
     * Create an object key for a new object.
     *
     * @param array $attributes  The attributes (in driver keys) of the
     *                           object being added.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeKey($attributes)
    {
        return isset($attributes['uid'])
            ? $attributes['uid']
            : $this->generateUID();
    }

    /**
     * Create an object key for a new object.
     *
     * @return string  A unique ID for the new object.
     */
    public function generateUID()
    {
        return method_exists($this->_wrapper, 'generateUID')
            ? $this->_wrapper->generateUID()
            : strval(new Horde_Support_Uuid());
    }

    /**
     * Creates a new Horde_Share.
     *
     * @param array  The params for the share.
     *
     * @return Horde_Share  The share object.
     */
    public function createShare($share_id, $params)
    {
        if (isset($params['params']['default']) &&
            ($params['params']['default'] === true)) {
            $share_id = $GLOBALS['registry']->getAuth();
        }

        return Turba::createShare($share_id, $params);
    }

    /**
     */
    public function checkDefaultShare($share, $srcConfig)
    {
        $params = @unserialize($share->get('params'));
        return isset($params['default'])
            ? $params['default']
            : false;
    }

}
