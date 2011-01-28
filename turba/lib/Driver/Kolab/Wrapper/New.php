<?php
/**
 * New Horde Turba driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Kolab_Wrapper_New extends Turba_Driver_Kolab_Wrapper
{
    /**
     * Internal cache of Kronolith_Event_kolab_new. eventID/UID is key
     *
     * @var array
     */
    protected $_contacts_cache;

    /**
     * Shortcut to the imap connection
     *
     * @var Kolab_IMAP
     */
    protected $_store = null;

    /**
     * Connect to the Kolab backend.
     *
     * @throws Turba_Exception
     */
    function connect()
    {
        parent::connect(1);

        $this->_store = &$this->_kolab->_storage;

        /* Fetch the contacts first */
        $raw_contacts = $this->_store->getObjectArray();
        if (!$raw_contacts) {
            $raw_contacts = array();
        }
        $contacts = array();
        foreach ($raw_contacts as $id => $contact) {
            if (isset($contact['email'])) {
                unset($contact['email']);
            }
            if (isset($contact['picture'])) {
                $name = $contact['picture'];
                if (isset($contact['_attachments'][$name])) {
                    $contact['photo'] =  $this->_store->_data->getAttachment($contact['_attachments'][$name]['key']);
                    $contact['phototype'] = $contact['_attachments'][$name]['type'];
                }
            }

            $contacts[$id] = $contact;
        }

        /* Now we retrieve distribution-lists */
        $result = $this->_store->setObjectType('distribution-list');
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }
        $groups = $this->_store->getObjectArray();
        if (!$groups) {
            $groups = array();
        }

        /* Revert to the original state */
        $result = $this->_store->setObjectType('contact');
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        /* Store the results in our cache */
        $this->_contacts_cache = array_merge($contacts, $groups);
    }

    /**
     * Searches the Kolab message store with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty
     * array, all records will be returned.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search($criteria, $fields)
    {
        $this->connect();

        if (!count($criteria)) {
            return $this->_contacts_cache;
        }

        // keep only entries matching criteria
        $ids = array();
        foreach ($criteria as $key => $criteria) {
            $ids[] = $this->_doSearch($criteria, strval($key), $this->_contacts_cache);
        }
        $ids = $this->_removeDuplicated($ids);

        /* Now we have a list of names, get the rest. */
        $this->_read('uid', $ids, $fields);

        Horde::logMessage(sprintf('Kolab returned %s results',
                                  count($result)), 'DEBUG');

        return array_values($result);
    }

    /**
     * Applies the filter criteria to a list of entries
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Array containing the ids of the selected entries.
     */
    protected function _doSearch($criteria, $glue, &$entries)
    {
        $ids = array();

        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])) {
                $ids[] = $this->_doSearch($vals['OR'], 'OR', $entries);
            } elseif (!empty($vals['AND'])) {
                $ids[] = $this->_doSearch($vals['AND'], 'AND', $entries);
            } else {
                /* If we are here, and we have a ['field'] then we
                 * must either do the 'AND' or the 'OR' search. */
                if (isset($vals['field'])) {
                    $ids[] = $this->_selectEntries($vals, $entries);
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $ids[] = $this->_doSearch($test['OR'], 'OR');
                        } elseif (!empty($test['AND'])) {
                            $ids[] = $this->_doSearch($test['AND'], 'AND');
                        } else {
                            $ids[] = $this->_doSearch(array($test), $glue);
                        }
                    }
                }
            }
        }

        if ($glue == 'AND') {
            $ids = $this->_getAND($ids);
        } elseif ($glue == 'OR') {
            $ids = $this->_removeDuplicated($ids);
        }

        return $ids;
    }

    /**
     * Applies one filter criterium to a list of entries
     *
     * @param $test          Test criterium
     * @param &$entries       List of fields to return.
     *
     * @return array  Array containing the ids of the selected entries
     */
    protected function _selectEntries($test, &$entries)
    {
        $ids = array();

        if (!isset($test['field'])) {
            Horde::logMessage('Search field not set. Returning all entries.', 'DEBUG');
            foreach ($entries as $entry) {
                $ids[] = $entry['uid'];
            }
        } else {
            $field = $test['field'];
            $value = isset($test['test'])
                ? $test['test']
                : '';

            // Special emails hack
            if ($field == 'email') {
                $field = 'emails';
                $test['op'] = 'LIKE';
                $test['begin'] = false;
            }
            if (!isset($test['op']) || $test['op'] == '=') {
                foreach ($entries as $entry) {
                    if (isset($entry[$field]) && $entry[$field] == $value) {
                        $ids[] = $entry['uid'];
                    }
                }
            } else {
                // 'op' is LIKE
                foreach ($entries as $entry) {
                    if (empty($value) ||
                        (isset($entry[$field]) &&
                         !empty($test['begin']) &&
                         (($pos = stripos($entry[$field], $value)) !== false) &&
                         ($pos == 0))) {
                        $ids[] = $entry['uid'];
                    }
                }
            }
        }

        return $ids;
    }

    /**
     * Returns only those names that are duplicated in $ids
     *
     * @param array $ids  A nested array of arrays containing names
     *
     * @return array  Array containing the 'AND' of all arrays in $ids
     */
    protected function _getAND($ids)
    {
        $matched = $results = array();

        /* If there is only 1 array, simply return it. */
        if (count($ids) < 2) {
            return $ids[0];
        }

        for ($i = 0; $i < count($ids); ++$i) {
            if (is_array($ids[$i])) {
                $results = array_merge($results, $ids[$i]);
            }
        }

        $search = array_count_values($results);
        foreach ($search as $key => $value) {
            if ($value == count($ids)) {
                $matched[] = $key;
            }
        }

        return $matched;
    }

    /**
     * Returns an array with all duplicate names removed.
     *
     * @param array $ids  Nested array of arrays containing names.
     *
     * @return array  Array containg the 'OR' of all arrays in $ids.
     */
    protected function _removeDuplicated($ids)
    {
        for ($i = 0; $i < count($ids); ++$i) {
            if (is_array($ids[$i])) {
                $unames = array_merge($unames, $ids[$i]);
            }
        }

        return array_unique($unames);
    }

    /**
     * Read the given data from the Kolab message store and returns the
     * result's fields.
     *
     * @param string $key    The primary key field to use (always 'uid' for
     *                       Kolab).
     * @param array $ids     Data identifiers
     * @param array $fields  List of fields to return.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $fields)
    {
        $this->connect();

        $results = array();

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $count = count($fields);
        foreach ($ids as $id) {
            if (in_array($id, array_keys($this->_contacts_cache))) {
                $object = $this->_contacts_cache[$id];

                $object_type = $this->_contacts_cache[$id]['__type'];
                if (!isset($object['__type']) || $object['__type'] == 'Object') {
                    if ($count) {
                        $result = array();
                        foreach ($fields as $field) {
                            if (isset($object[$field])) {
                                $result[$field] = $object[$field];
                            }
                        }
                        $results[] = $result;
                    } else {
                        $results[] = $object;
                    }
                } else {
                    $member_ids = array();
                    if (isset($object['member'])) {
                        foreach ($object['member'] as $member) {
                            if (isset($member['uid'])) {
                                $member_ids[] = $member['uid'];
                                continue;
                            }
                            $display_name = $member['display-name'];
                            $smtp_address = $member['smtp-address'];
                            $criteria = array(
                                'AND' => array(
                                    array(
                                        'field' => 'full-name',
                                        'op' => 'LIKE',
                                        'test' => $display_name,
                                        'begin' => false,
                                    ),
                                    array(
                                        'field' => 'emails',
                                        'op' => 'LIKE',
                                        'test' => $smtp_address,
                                        'begin' => false,
                                    ),
                                ),
                            );
                            $fields = array('uid');

                            // we expect only one result here!!!
                            $contacts = $this->_search($criteria, $fields);

                            // and drop everything else except the first search result
                            $member_ids[] = $contacts[0]['uid'];
                        }
                        $object['__members'] = serialize($member_ids);
                        unset($object['member']);
                    }
                    $results[] = $object;;
                }
            }
        }

        return $results;
    }

    /**
     * Adds the specified object to the Kolab message store.
     *
     * TODO
     *
     * @throws Turba_Exception
     */
    protected function _add($attributes)
    {
        $this->connect();

        $attributes['full-name'] = $attributes['last-name'];
        if (isset($attributes['middle-names'])) {
            $attributes['full-name'] = $attributes['middle-names'] . ' ' . $attributes['full-name'];
        }
        if (isset($attributes['given-name'])) {
            $attributes['full-name'] = $attributes['given-name'] . ' ' . $attributes['full-name'];
        }

        $this->_store($attributes);
    }

    /**
     * Updates an existing object in the Kolab message store.
     *
     * TODO
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    function _save($object_key, $object_id, $attributes)
    {
        $this->connect();

        if ($object_key != 'uid') {
            throw new Turba_Exception(sprintf('Key for saving must be \'uid\' not %s!', $object_key));
        }

        return $this->_store($attributes, $object_id);
    }

    /**
     * Stores an object in the Kolab message store.
     *
     * TODO
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _store($attributes, $object_id = null)
    {
        $group = false;
        if (isset($attributes['__type']) && $attributes['__type'] == 'Group') {
            $group = true;
            $result = $this->_store->setObjectType('distribution-list');
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
            $this->_convertMembers($attributes);
        }

        if (isset($attributes['photo']) && isset($attributes['phototype'])) {
            $attributes['_attachments']['photo.attachment'] = array(
                'type' => $attributes['phototype'],
                'content' => $attributes['photo']
            );
            $attributes['picture'] = 'photo.attachment';
            unset($attributes['photo'], $attributes['phototype']);
        }

        $result = $this->_store->save($attributes, $object_id);
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        if ($group) {
            $result = $this->_store->setObjectType('contact');
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
        }

        return $object_id;
    }

    /**
     * TODO
     */
    function _convertMembers(&$attributes)
    {
        if (isset($attributes['__members'])) {
            $member_ids = unserialize($attributes['__members']);
            $attributes['member'] = array();
            foreach ($member_ids as $member_id) {
                if (isset($this->_contacts_cache[$member_id])) {
                    $member = $this->_contacts_cache[$member_id];
                    $mail = array('uid' => $member_id);
                    if (!empty($member['full-name'])) {
                        $mail['display-name'] = $member['full-name'];
                    }
                    if (!empty($member['emails'])) {
                        $emails = explode(',', $member['emails']);
                        $mail['smtp-address'] = trim($emails[0]);
                        if (!isset($mail['display-name'])) {
                            $mail['display-name'] = $mail['smtp-address'];
                        }
                    }
                    $attributes['member'][] = $mail;
                }
            }
            unset($attributes['__members']);
        }
    }


    /**
     * Removes the specified object from the Kolab message store.
     *
     * @throws Turba_Exception
     */
    function _delete($object_key, $object_id)
    {
        $this->connect();

        if ($object_key != 'uid') {
            throw new Turba_Exception(sprintf('Key for saving must be a UID not %s!', $object_key));
        }

        if (!in_array($object_id, array_keys($this->_contacts_cache))) {
            throw new Turba_Exception(sprintf(_("Object with UID %s does not exist!"), $object_id));
        }

        $group = (isset($this->_contacts_cache[$object_id]['__type']) &&
                  $this->_contacts_cache[$object_id]['__type'] == 'Group');

        if ($group) {
            $result = $this->_store->setObjectType('distribution-list');
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
        }

        $result = $this->_store->delete($object_id);

        if ($group) {
            $result = $this->_store->setObjectType('contact');
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
        }

        return $result;
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @throws Turba_Exception
     */
    protected function _deleteAll($sourceName = null)
    {
        $this->connect();

        /* Delete contacts */
        $result = $this->_store->deleteAll();
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        /* Delete groups */
        $result = $this->_store->setObjectType('distribution-list');
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        $result = $this->_store->deleteAll();
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }

        /* Revert to the original state */
        $result = $this->_store->setObjectType('contact');
        if ($result instanceof PEAR_Error) {
            throw new Turba_Exception($result);
        }
    }

    /**
     * Create an object key for a new object.
     *
     * @return string  A unique ID for the new object.
     */
    public function generateUID()
    {
        do {
            $key = strval(new Horde_Support_Uuid());
        } while (in_array($key, array_keys($this->_contacts_cache)));

        return $key;
    }

}
