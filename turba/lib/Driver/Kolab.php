<?php
/**
 * Horde Turba driver for the Kolab IMAP Server.
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Stuart Binge <omicron@mighty.co.za>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Driver_Kolab extends Turba_Driver
{
    /**
     * The Kolab_Storage backend.
     *
     * @var Horde_Kolab_Storage
     */
    protected $_kolab;

    /**
     * Indicates if the driver has been connected to a specific addressbook or
     * not.
     *
     * @var boolean
     */
    protected $_connected = false;

    /**
     * The current addressbook.
     *
     * @var Horde_Kolab_Storage_Data
     */
    protected $_data;

    /**
     * The current addressbook represented as share.
     *
     * @var Horde_Share_Object
     */
    protected $_share;

    /**
     * The cached contacts.
     *
     * @var array
     */
    protected $_contacts_cache;

    /**
     * What can this backend do?
     *
     * @var array
     */
    protected $_capabilities = array(
        'delete_addressbook' => true,
        'delete_all' => true,
    );

    /**
     * Attempts to open a Kolab Groupware folder.
     */
    public function __construct($name = '', $params = array())
    {
        if (empty($params['storage'])) {
            throw new InvalidArgumentException('Missing required storage handler.');
        }
        $this->_kolab = $params['storage'];
        unset($params['storage']);

        if (isset($params['share'])) {
            $this->_share = $params['share'];
        }
        if (isset($params['name'])) {
            $name = $params['name'];
        }

        parent::__construct($name, $params);
    }

    /**
     * Translates the keys of the first hash from the generalized Turba
     * attributes to the driver-specific fields. The translation is based on
     * the contents of $this->map.
     *
     * @param array $hash  Hash using Turba keys.
     *
     * @return array  Translated version of $hash.
     */
    public function toDriverKeys(array $hash)
    {
        $hash = parent::toDriverKeys($hash);

        if (isset($hash['name'])) {
            $hash['name'] = array('full-name' => $hash['name']);
        }

        if (isset($hash['emails'])) {
            $list = new Horde_Mail_Rfc822_List($hash['emails']);
            $hash['email'] = array();
            foreach ($list as $address) {
                $hash['email'][] = array('smtp-address' => $address->bare_address);
            }
            unset($hash['emails']);
        }

        /* TODO: use Horde_Kolab_Format_Xml_Type_Composite_* */
        foreach (array('given-name',
                       'middle-names',
                       'last-name',
                       'initials',
                       'prefix',
                       'suffix') as $sub) {
            if (isset($hash[$sub])) {
                $hash['name'][$sub] = $hash[$sub];
                unset($hash[$sub]);
            }
        }

        $hash['phone'] = array();
        foreach (array('phone-business1',
                       'phone-business2',
                       'phone-businessfax',
                       'phone-car',
                       'phone-company',
                       'phone-home1',
                       'phone-home2',
                       'phone-homefax',
                       'phone-mobile',
                       'phone-pager',
                       'phone-radio',
                       'phone-assistant') as $sub) {
            if (isset($hash[$sub])) {
                $hash['phone'][] = array('type' => substr($sub, 6),
                                         'number' => $hash[$sub]);
                unset($hash[$sub]);
            }
        }

        $hash['address'] = array();
        $address = array();
        foreach (array('addr-business-street',
                       'addr-business-locality',
                       'addr-business-region',
                       'addr-business-postal-code',
                       'addr-business-country') as $sub) {
            if (isset($hash[$sub])) {
                $address[substr($sub, 14)] = $hash[$sub];
                unset($hash[$sub]);
            }
        }
        if ($address) {
            $address['type'] = 'business';
            $hash['address'][] = $address;
        }
        $address = array();
        foreach (array('addr-home-street',
                       'addr-home-locality',
                       'addr-home-region',
                       'addr-home-postal-code',
                       'addr-home-country') as $sub) {
            if (isset($hash[$sub])) {
                $address[substr($sub, 10)] = $hash[$sub];
                unset($hash[$sub]);
            }
        }
        if ($address) {
            $address['type'] = 'home';
            $hash['address'][] = $address;
        }

        if (isset($hash['categories'])) {
            $hash['categories'] = array($hash['categories']);
        }

        if (isset($hash['birthday'])) {
            $hash['birthday'] = new DateTime($hash['birthday']);
        }
        if (isset($hash['anniversary'])) {
            $hash['anniversary'] = new DateTime($hash['anniversary']);
        }

        return $hash;
    }

    /**
     * Return the Kolab data handler for the current address book.
     *
     * @return Horde_Kolab_Storage_Data The data handler.
     */
    protected function _getData()
    {
        if ($this->_data === null) {
            if (!empty($this->_share)) {
                $this->_data = $this->_kolab->getData(
                    $this->_share->get('folder'),
                    'contact'
                );
                $this->setContactOwner($this->_share->get('owner'));
            } elseif (empty($this->_name)) {
                throw new Turba_Exception(
                    'The addressbook has been left undefined but is required!'
                );
            }
            $this->_data = $this->_getDataForAddressbook($this->_name);
        }
        return $this->_data;
    }

    /**
     * Return the Kolab data handler for the specified addressbook.
     *
     * @param string $addressbook The addressbook name.
     *
     * @return Horde_Kolab_Storage_Date The data handler.
     */
    protected function _getDataForAddressbook($addressbook)
    {
        $share = $GLOBALS['injector']
            ->getInstance('Turba_Shares')
            ->getShare($addressbook);
        $this->setContactOwner($share->get('owner'));
        return $this->_kolab->getData($share->get('folder'), 'contact');
    }

    /**
     * Connect to the Kolab backend.
     *
     * @throws Turba_Exception
     */
    public function connect()
    {
        if ($this->_connected) {
            return;
        }

        /* Fetch the contacts first */
        $raw_contacts = $this->_getData()->getObjects();
        if (!$raw_contacts) {
            $raw_contacts = array();
        }
        $contacts = array();
        foreach ($raw_contacts as $id => $contact) {
            $contact = $contact->getData();
            if (isset($contact['picture'])) {
                $name = $contact['picture'];
                if (isset($contact['_attachments'][$name])) {
                    $contact['photo'] = $this->_getData()
                        ->getAttachment($contact['_attachments'][$name]['key']);
                    $contact['phototype'] = $contact['_attachments'][$name]['type'];
                }
            }

            if (isset($contact['name'])) {
                foreach ($contact['name'] as $detail => $value) {
                    $contact[$detail] = $value;
                }
                unset($contact['name']);
            }

            if (isset($contact['phone'])) {
                foreach ($contact['phone'] as $phone) {
                    $contact['phone-' . $phone['type']] = $phone['number'];
                }
                unset($contact['phone']);
            }

            if (isset($contact['email'])) {
                $contact['emails'] = array();
                foreach ($contact['email'] as $email) {
                    $contact['emails'][] = $email['smtp-address'];
                }
                if ($contact['emails']) {
                    $contact['email'] = $contact['emails'][0];
                } else {
                    unset($contact['email']);
                }
                $contact['emails'] = implode(', ', $contact['emails']);
            }

            if (isset($contact['address'])) {
                foreach ($contact['address'] as $address) {
                    foreach ($address as $detail => $value) {
                        if ($detail != 'type') {
                            $contact['addr-' . $address['type'] . '-' . $detail] = $value;
                        }
                    }
                }
                unset($contact['address']);
            }

            if (isset($contact['categories'])) {
                if (empty($contact['categories'])) {
                    $contact['categories'] = '';
                } else {
                    $contact['categories'] = $contact['categories'][0];
                }
            }

            if (isset($contact['birthday'])) {
                $contact['birthday'] = $contact['birthday']->format('Y-m-d');
            }
            if (isset($contact['anniversary'])) {
                $contact['anniversary'] = $contact['anniversary']->format('Y-m-d');
            }

            $contacts[$id] = $contact;
        }

        /* Now we retrieve distribution-lists */
        $groups = array();
        //@todo: group support
        /* $result = $this->_store->setObjectType('distribution-list'); */
        /* $groups = $this->_store->getObjects(); */
        /* if (!$groups) { */
        /*     $groups = array(); */
        /* } */

        /* Store the results in our cache */
        $this->_contacts_cache = array_merge($contacts, $groups);

        $this->_connected = true;
    }

    /**
     * Searches the address book with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria    Array containing the search criteria.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields,
                               array $blobFields = array(), $count_only = false)
    {
        $this->connect();

        if (!count($criteria)) {
            return $count_only
                ? count($this->_contacts_cache)
                : array_values($this->_contacts_cache);
        }

        // keep only entries matching criteria
        $ids = array();
        foreach ($criteria as $key => $criteria) {
            $ids[] = $this->_doSearch($criteria, strval($key));
        }
        $ids = $this->_removeDuplicated($ids);

        /* Now we have a list of names, get the rest. */
        $result = $this->_read('uid', $ids, null, $fields);

        Horde::logMessage(sprintf('Kolab returned %s results',
                                  count($result)), 'DEBUG');

        return $count_only ? count($result) : array_values($result);
    }

    /**
     * Applies the filter criteria to a list of entries
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Array containing the ids of the selected entries.
     */
    protected function _doSearch($criteria, $glue)
    {
        $ids = array();

        foreach ($criteria as $vals) {
            if (!empty($vals['OR'])) {
                $ids[] = $this->_doSearch($vals['OR'], 'OR');
            } elseif (!empty($vals['AND'])) {
                $ids[] = $this->_doSearch($vals['AND'], 'AND');
            } else {
                /* If we are here, and we have a ['field'] then we
                 * must either do the 'AND' or the 'OR' search. */
                if (isset($vals['field'])) {
                    $ids[] = $this->_selectEntries($vals);
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
     *
     * @return array  Array containing the ids of the selected entries
     */
    protected function _selectEntries($test)
    {
        $ids = array();

        if (!isset($test['field'])) {
            Horde::logMessage('Search field not set. Returning all entries.', 'DEBUG');
            foreach ($this->_contacts_cache as $entry) {
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
                foreach ($this->_contacts_cache as $entry) {
                    if (isset($entry[$field]) && $entry[$field] == $value) {
                        $ids[] = $entry['uid'];
                    }
                }
            } else {
                // 'op' is LIKE
                foreach ($this->_contacts_cache as $entry) {
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
        $unames = array();
        for ($i = 0; $i < count($ids); ++$i) {
            if (is_array($ids[$i])) {
                $unames = array_merge($unames, $ids[$i]);
            }
        }

        return array_unique($unames);
    }

    /**
     * Reads the given data from the address book and returns the results.
     *
     * @param string $key        The primary key field to use.
     * @param mixed $ids         The ids of the contacts to load.
     * @param string $owner      Only return contacts owned by this user.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  Array of fields containing binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, array $fields,
                             array $blobFields = array())
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
     * Adds the specified contact to the addressbook.
     *
     * @param array $attributes  The attribute values of the contact.
     * @param array $blob_fields TODO
     *
     * @throws Turba_Exception
     */
    protected function _add(array $attributes, array $blob_fields = array())
    {
        $this->connect();
        $this->_store($attributes);
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
        $this->connect();

        if ($object_key != 'uid') {
            throw new Turba_Exception(sprintf('Key for saving must be a UID not %s!', $object_key));
        }

        if (!isset($this->_contacts_cache[$object_id])) {
            throw new Turba_Exception(sprintf(_("Object with UID %s does not exist!"), $object_id));
        }

        $group = isset($this->_contacts_cache[$object_id]['__type']) &&
            $this->_contacts_cache[$object_id]['__type'] == 'Group';

        if ($group) {
            //@todo: group support
            //$result = $this->_store->setObjectType('distribution-list');
        }

        $result = $this->_getData()->delete($object_id);

        return $result;
    }

    /**
     * Deletes all contacts from a specific address book.
     *
     * @param string $sourceName  The source to remove all contacts from.
     *
     * @return array  An array of UIDs
     * @throws Turba_Exception
     */
    protected function _deleteAll($sourceName = null)
    {
        $this->connect();
        $uids = array_keys($this->_contacts_cache);

        /* Delete contacts */
        $this->_getData()->deleteAll();

        /* Delete groups */
        //@todo: group support
        //$result = $this->_store->setObjectType('distribution-list');
        //$result = $this->_store->deleteAll();

        return $uids;
    }

    /**
     * Saves the specified object in the SQL database.
     *
     * @param Turba_Object $object  The object to save
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _save(Turba_Object $object)
    {
        $this->connect();
        return $this->_store($this->toDriverKeys($object->getAttributes()),
                             $object->getValue('__uid'));
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
            return;
            //@todo: group support
            /* $group = true; */
            /* $result = $this->_store->setObjectType('distribution-list'); */
            /* $this->_convertMembers($attributes); */
        }

        if (isset($attributes['photo']) && isset($attributes['phototype'])) {
            $attributes['_attachments']['photo.attachment'] = array(
                'type' => $attributes['phototype'],
                'content' => $attributes['photo']
            );
            $attributes['picture'] = 'photo.attachment';
            unset($attributes['photo'], $attributes['phototype']);
        }

        if ($object_id === null) {
            $object_id = $this->_getData()->create($attributes);
        } else {
            $object_id = $this->_getData()->modify($attributes);
        }
        /* if ($group) {
            $result = $this->_store->setObjectType('contact');
        } */

        /* Invalidate cache. */
        $this->_connected = false;

        return $object_id;
    }

    /**
     * TODO
     */
    protected function _convertMembers(&$attributes)
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
     * Create an object key for a new object.
     *
     * @param array $attributes  The attributes (in driver keys) of the
     *                           object being added.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeKey(array $attributes)
    {
        return isset($attributes['uid'])
            ? $attributes['uid']
            : $this->_generateUid();
    }

    /**
     * Creates an object UID for a new object.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _makeUid()
    {
        return $this->_generateUid();
    }

    /**
     * Create an object key for a new object.
     *
     * @return string  A unique ID for the new object.
     */
    protected function _generateUid()
    {
        return $this->_getData()->generateUID();
    }

    /**
     * Creates a new Horde_Share for this source type.
     *
     * @param string $share_name  The share name
     * @param array  $params      The params for the share.
     *
     * @return Horde_Share  The share object.
     */
    public function createShare($share_name, array $params)
    {
        if (!isset($params['name'])) {
            $params['name'] = _('Contacts');
        }
        if (!empty($params['params']['default'])) {
            $params['default'] = true;
            unset($params['params']['default']);
        }
        return Turba::createShare($share_name, $params);
    }

    /**
     * Check if the passed in share is the default share for this source.
     *
     * @param Horde_Share_Object $share  The share object.
     * @param array $srcconfig           The cfgSource entry for the share.
     *
     * @return boolean TODO
     */
    public function checkDefaultShare(Horde_Share_Object $share,
                                      array $srcconfig)
    {
        $params = @unserialize($share->get('params'));
        return isset($params['default'])
            ? $params['default']
            : false;
    }
}
