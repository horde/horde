<?php
/**
 * Turba directory driver implementation for Horde Preferences - very simple,
 * lightweight container.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Prefs extends Turba_Driver
{
    /**
     * Returns all entries - searching isn't implemented here for now. The
     * parameters are simply ignored.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    protected function _search($criteria, $fields)
    {
        return array_values($this->_getAddressBook());
    }

    /**
     * Reads the given data from the preferences and returns the result's
     * fields.
     *
     * @param array $criteria  Search criteria.
     * @param string $id       Data identifier.
     * @param array $fields    List of fields to return.
     *
     * @return  Hash containing the search results.
     */
    protected function _read($criteria, $ids, $fields)
    {
        $book = $this->_getAddressBook();
        $results = array();
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        foreach ($ids as $id) {
            if (isset($book[$id])) {
                $results[] = $book[$id];
            }
        }

        return $results;
    }

    /**
     * Adds the specified object to the preferences.
     *
     * @param array $attributes  TODO
     */
    protected function _add($attributes)
    {
        $book = $this->_getAddressBook();
        $book[$attributes['id']] = $attributes;
        $this->_setAddressbook($book);
    }

    /**
     * TODO
     */
    protected function _canAdd()
    {
        return true;
    }

    /**
     * Deletes the specified object from the preferences.
     *
     * @param $object_key TODO
     * @param $object_id  TODO
     */
    protected function _delete($object_key, $object_id)
    {
        $book = $this->_getAddressBook();
        unset($book[$object_id]);
        $this->_setAddressbook($book);
    }

    /**
     * Saves the specified object in the preferences.
     *
     * @param $object TODO
     */
    function _save($object)
    {
        list($object_key, $object_id) = each($this->toDriverKeys(array('__key' => $object->getValue('__key'))));
        $attributes = $this->toDriverKeys($object->getAttributes());

        $book = $this->_getAddressBook();
        $book[$object_id] = $attributes;
        $this->_setAddressBook($book);
    }

    /**
     * TODO
     *
     * @return TODO
     */
    protected function _getAddressBook()
    {
        global $prefs;

        $val = $prefs->getValue('prefbooks');
        if (!empty($val)) {
            $prefbooks = unserialize($val);
            return $prefbooks[$this->_params['name']];
        }

        return array();
    }

    /**
     * TODO
     *
     * @param $addressbook TODO
     *
     * @return TODO
     */
    protected function _setAddressBook($addressbook)
    {
        global $prefs;

        $val = $prefs->getValue('prefbooks');
        $prefbooks = empty($val)
            ? array()
            : unserialize($val);

        $prefbooks[$this->_params['name']] = $addressbook;
        $prefs->setValue('prefbooks', serialize($prefbooks));
        $prefs->store();
    }

}
