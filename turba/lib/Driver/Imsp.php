<?php
/**
 * Turba directory driver implementation for an IMSP server.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/asl.php ASL
 * @package  Turba
 */
class Turba_Driver_Imsp extends Turba_Driver
{
    /**
     * Handle for the IMSP connection.
     *
     * @var Net_IMSP
     */
    protected $_imsp;

    /**
     * The name of the addressbook.
     *
     * @var string
     */
    protected $_bookName  = '';

    /**
     * Holds if we are authenticated.
     *
     * @var boolean
     */
    protected $_authenticated = '';

    /**
     * Holds name of the field indicating an IMSP group.
     *
     * @var string
     */
    protected $_groupField = '';

    /**
     * Holds value that $_groupField will have if entry is an IMSP group.
     *
     * @var string
     */
    protected $_groupValue = '';

    /**
     * Used to set if the current search is for contacts only.
     *
     * @var boolean
     */
    protected $_noGroups = '';

    /**
     * Driver capabilities.
     *
     * @var array
     */
    protected $_capabilities = array(
        'delete_all' => true,
        'delete_addressbook' => true
    );

    /**
     * Constructs a new Turba imsp driver object.
     *
     * @param array $params  Hash containing additional configuration
     *                       parameters.
     */
    public function __construct($params)
    {
        parent::__construct($name, $params);

        $this->params       = $params;
        $this->_groupField  = $params['group_id_field'];
        $this->_groupValue  = $params['group_id_value'];
        $this->_myRights    = $params['my_rights'];
        $this->_perms       = $this->_aclToHordePerms($params['my_rights']);

        global $conf;

        $this->_bookName = $this->getContactOwner();
        $this->_imsp = Net_IMSP::singleton('Book', $this->params);
        $result = $this->_imsp->init();
        if ($result instanceof PEAR_Error) {
            $this->_authenticated = false;
            throw new Turba_Exception($result);
        }

        if (!empty($conf['log'])) {
            $logParams = $conf['log'];
            $result = $this->_imsp->setLogger($conf['log']);
            if ($result instanceof PEAR_Error) {
                throw new Turba_Exception($result);
            }
        }

        Horde::logMessage('IMSP Driver initialized for ' . $this->_bookName, 'DEBUG');
        $this->_authenticated = true;
    }

    /**
     * Returns all entries matching $critera.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    protected function _search($criteria, $fields, $blobFields = array())
    {
        $query = $results = array();

        if (!$this->_authenticated) {
            return $query;
        }

        /* Get the search criteria. */
        if (count($criteria)) {
            foreach ($criteria as $key => $vals) {
                $names = (strval($key) == 'OR')
                    ? $this->_doSearch($vals, 'OR')
                    : $this->_doSearch($vals, 'AND');
            }
        }

        /* Now we have a list of names, get the rest. */
        $result = $this->_read('name', $names, null, $fields);
        if (is_array($result)) {
            $results = $result;
        }

        Horde::logMessage(sprintf('IMSP returned %s results', count($results)), 'DEBUG');

        return array_values($results);
    }

    /**
     * Reads the given data from the IMSP server and returns the
     * results.
     *
     * @param string $key    The primary key field to use (always 'name' for
     *                       IMSP).
     * @param mixed $ids     The ids of the contacts to load.
     * @param string $owner  Only return contacts owned by this user.
     * @param array $fields  List of fields to return.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _read($key, $ids, $owner, $fields)
    {
        $results = array();

        if (!$this->_authenticated) {
            return $results;
        }

        $ids = array_values($ids);
        $idCount = count($ids);
        $IMSPGroups = $members = $tmembers = array();

        for ($i = 0; $i < $idCount; ++$i) {
            $result = array();

            $temp = isset($IMSPGroups[$ids[$i]])
                ? $IMSPGroups[$ids[$i]]
                : $this->_imsp->getEntry($this->_bookName, $ids[$i]);
            if ($temp instanceof PEAR_Error) {
                continue;
            }

            $temp['fullname'] = $temp['name'];
            $isIMSPGroup = false;
            if (!isset($temp['__owner'])) {
                $temp['__owner'] = $GLOBALS['registry']->getAuth();
            }

            if ((isset($temp[$this->_groupField])) &&
                ($temp[$this->_groupField] == $this->_groupValue)) {
                if ($this->_noGroups) {
                    continue;
                }
                if (!isset($IMSPGroups[$ids[$i]])) {
                    $IMSPGroups[$ids[$i]] = $temp;
                }
                // move group ids to end of list
                if ($idCount > count($IMSPGroups) &&
                    $idCount - count($IMSPGroups) > $i) {
                    $ids[] = $ids[$i];
                    unset($ids[$i]);
                    $ids = array_values($ids);
                    --$i;
                    continue;
                }
                $isIMSPGroup = true;
            }
            // Get the group members that might have been added from other
            // IMSP applications, but only if we need more information than
            // the group name
            if ($isIMSPGroup &&
                array_search('__members', $fields) !== false) {
                if (isset($temp['email'])) {
                    $emailList = $this->_getGroupEmails($temp['email']);
                    $count = count($emailList);
                    for ($j = 0; $j < $count; ++$j) {
                        $needMember = true;
                        foreach ($results as $curResult) {
                            if (!empty($curResult['email']) &&
                                strtolower($emailList[$j]) == strtolower(trim($curResult['email']))) {
                                $members[] = $curResult['name'];
                                $needMember = false;
                            }
                        }
                        if ($needMember) {
                            $memberName = $this->_imsp->search
                                ($this->_bookName,
                                 array('email' => trim($emailList[$j])));

                            if (count($memberName)) {
                                $members[] = $memberName[0];
                            }
                        }
                    }
                }
                if (!empty($temp['__members'])) {
                    $tmembers = @unserialize($temp['__members']);
                }

                // TODO: Make sure that we are using the correct naming
                // convention for members regardless of if we are using
                // shares or not. This is needed to assure groups created
                // while not using shares won't be lost when transitioning
                // to shares and visa versa.
                //$tmembers = $this->_checkMemberFormat($tmembers);

                $temp['__members'] = serialize($this->_removeDuplicated(
                                               array($members, $tmembers)));
                $temp['__type'] = 'Group';
                $temp['email'] = null;
                $result = $temp;
            } else {
                // IMSP contact.
                $count = count($fields);
                for ($j = 0; $j < $count; ++$j) {
                    if (isset($temp[$fields[$j]])) {
                        $result[$fields[$j]] = $temp[$fields[$j]];
                    }
                }
            }

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Adds the specified object to the IMSP server.
     */
    protected function _add($attributes)
    {
        /* We need to map out Turba_Object_Groups back to IMSP groups before
         * writing out to the server. We need to array_values() it in
         * case an entry was deleted from the group. */
        if ($attributes['__type'] == 'Group') {
            /* We may have a newly created group. */
            $attributes[$this->_groupField] = $this->_groupValue;
            if (!isset($attributes['__members'])) {
                $attributes['__members'] = '';
                $attributes['email'] = ' ';
            }
            $temp = unserialize($attributes['__members']);
            if (is_array($temp)) {
                $members = array_values($temp);
            } else {
                $members = array();
            }

            // This searches the current IMSP address book to see if
            // we have a match for this member before adding to email
            // attribute since IMSP groups in other IMSP aware apps
            // generally require an existing conact entry in the current
            // address book for each group member (this is necessary for
            // those sources that may be used both in AND out of Horde).
            try {
                $result = $this->_read('name', $members, null, array('email'));
                $count = count($result);
                for ($i = 0; $i < $count; ++$i) {
                    if (isset($result[$i]['email'])) {
                        $contact = sprintf("%s<%s>\n", $members[$i],
                                           $result[$i]['email']);
                        $attributes['email'] .= $contact;
                    }
                }
            } catch (Turba_Exception $e) {}
        }

        unset($attributes['__type'], $attributes['fullname']);
        if (!$this->params['contact_ownership']) {
            unset($attributes['__owner']);
        }

        return $this->_imsp->addEntry($this->_bookName, $attributes);
    }

    /**
     * TODO
     */
    protected function _canAdd()
    {
        return true;
    }

    /**
     * Deletes the specified object from the IMSP server.
     *
     * @throws Turba_Exception
     */
    protected function _delete($object_key, $object_id)
    {
        $res = $this->_imsp->deleteEntry($this->_bookName, $object_id);
        if ($res instanceof PEAR_Error) {
            throw new Turba_Exception($res);
        }
    }

    /**
     * Deletes the address book represented by this driver from the IMSP server.
     *
     * @throws Turba_Exception
     */
     protected function _deleteAll()
     {
         $res = $this->_imsp->deleteAddressbook($this->_bookName);
         if ($res instanceof PEAR_Error) {
             throw new Turba_Exception($res);
         }
     }

    /**
     * Saves the specified object to the IMSP server.
     *
     * @param Turba_Object $object  The object to save/update.
     *
     * @return string  The object id, possibly updated.
     * @throws Turba_Exception
     */
    protected function _save($object)
    {
        list($object_key, $object_id) = each($this->toDriverKeys(array('__key' => $object->getValue('__key'))));
        $attributes = $this->toDriverKeys($object->getAttributes());

        /* Check if the key changed, because IMSP will just write out
         * a new entry without removing the previous one. */
        if ($attributes['name'] != $this->_makeKey($attributes)) {
            $this->_delete($object_key, $attributes['name']);
            $attributes['name'] = $this->_makeKey($attributes);
            $object_id = $attributes['name'];
        }

        $this->_add($attributes);

        return $object_id;
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
        return $attributes['fullname'];
    }

    /**
     * Parses out $emailText into an array of pure email addresses
     * suitable for searching the IMSP datastore with.
     *
     * @param string $emailText  Single string containing email addressses.
     *
     * @return array  Pure email address.
     */
    protected function _getGroupEmails($emailText)
    {
        preg_match_all("(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})", $emailText, $matches);
        return $matches[0];
    }

    /**
     * Parses the search criteria, requests the individual searches from the
     * server and performs any necessary ANDs / ORs on the results.
     *
     * @param array  $criteria  Array containing the search criteria.
     * @param string $glue      Type of search to perform (AND / OR).
     *
     * @return array  Array containing contact names that match $criteria.
     */
    protected function _doSearch($criteria, $glue)
    {
        $results = array();
        $names = array();
        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])) {
                $results[] = $this->_doSearch($vals['OR'], 'OR');
            } elseif (!empty($vals['AND'])) {
                $results[] = $this->_doSearch($vals['AND'], 'AND');
            } else {
                /* If we are here, and we have a ['field'] then we
                 * must either do the 'AND' or the 'OR' search. */
                if (isset($vals['field'])) {
                    $results[] = $this->_sendSearch($vals);
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $results[] = $this->_doSearch($test['OR'], 'OR');
                        } elseif (!empty($test['AND'])) {
                            $results[] = $this->_doSearch($test['AND'], 'AND');
                        } else {
                            $results[] = $this->_doSearch(array($test), $glue);
                        }
                    }
                }
            }
        }

        return ($glue == 'AND')
            ? $this->_getDuplicated($results)
            : $this->_removeDuplicated($results);
    }

    /**
     * Sends a search request to the server.
     *
     * @param array $criteria  Array containing the search critera.
     *
     * @return array  Array containing a list of names that match the search.
     */
    function _sendSearch($criteria)
    {
        global $conf;

        $names = '';
        $imspSearch = array();
        $searchkey = $criteria['field'];
        $searchval = $criteria['test'];
        $searchop = $criteria['op'];
        $hasName = false;
        $this->_noGroups = false;
        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        $key = implode(".", array_merge($criteria, array($this->_bookName)));

        /* Now make sure we aren't searching on a dynamically created
         * field. */
        switch ($searchkey) {
        case 'fullname':
            if (!$hasName) {
                $searchkey = 'name';
                $hasName = true;
            } else {
                $searchkey = '';
            }
            break;

        case '__owner':
            if (!$this->params['contact_ownership']) {
                $searchkey = '';
                $hasName = true;
            }
            break;
        }

        /* Are we searching for only Turba_Object_Groups or Turba_Objects?
         * This is needed so the 'Show Lists' and 'Show Contacts'
         * links work correctly in Turba. */
        if ($searchkey == '__type') {
            switch ($searchval) {
            case 'Group':
                $searchkey = $this->_groupField;
                $searchval = $this->_groupValue;
                break;

            case 'Object':
                if (!$hasName) {
                    $searchkey = 'name';
                    $searchval = '';
                    $hasName = true;
                } else {
                    $searchkey = '';
                }
                $this->_noGroups = true;
                break;
            }
        }

        if (!$searchkey == '') {
            // Check $searchval for content and for strict matches.
            if (strlen($searchval) > 0) {
                if ($searchop == 'LIKE') {
                    $searchval = '*' . $searchval . '*';
                }
            } else {
                $searchval = '*';
            }
            $imspSearch[$searchkey] = $searchval;
        }
        if (!count($imspSearch)) {
            $imspSearch['name'] = '*';
        }

        /* Finally get to the command.  Check the cache first, since each
         * 'Turba' search may consist of a number of identical IMSP
         * searchaddress calls in order for the AND and OR parts to work
         * correctly.  15 Second lifetime should be reasonable for this. This
         * should reduce load on IMSP server somewhat.*/
        $results = $cache->get($key, 15);

        if ($results) {
            $names = unserialize($results);
        }

        if (!$names) {
            $names = $this->_imsp->search($this->_bookName, $imspSearch);
            if ($names instanceof PEAR_Error) {
                $GLOBALS['notification']->push($names, 'horde.error');
            } else {
                $cache->set($key, serialize($names));
                return $names;
            }
        } else {
            return $names;
        }
    }

    /**
     * Returns only those names that are duplicated in $names
     *
     * @param array $names  A nested array of arrays containing names
     *
     * @return array  Array containing the 'AND' of all arrays in $names
     */
    protected function _getDuplicated($names)
    {
        $matched = $results = array();

        /* If there is only 1 array, simply return it. */
        if (count($names) < 2) {
            return $names[0];
        }

        for ($i = 0; $i < count($names); ++$i) {
            if (is_array($names[$i])) {
                $results = array_merge($results, $names[$i]);
            }
        }

        $search = array_count_values($results);
        foreach ($search as $key => $value) {
            if ($value > 1) {
                $matched[] = $key;
            }
        }

        return $matched;
    }

    /**
     * Returns an array with all duplicate names removed.
     *
     * @param array $names  Nested array of arrays containing names.
     *
     * @return array  Array containg the 'OR' of all arrays in $names.
     */
    protected function _removeDuplicated($names)
    {
        $unames = array();
        for ($i = 0; $i < count($names); ++$i) {
            if (is_array($names[$i])) {
                $unames = array_merge($unames, $names[$i]);
            }
        }

        return array_unique($unames);
    }

    /**
     * Checks if the current user has the requested permission
     * on this source.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  true if user has permission, false otherwise.
     */
    public function hasPermission($perm)
    {
        return $this->_perms & $perm;
    }

    /**
     * Converts an acl string to a Horde Permissions bitmask.
     *
     * @param string $acl  A standard, IMAP style acl string.
     *
     * @return integer  Horde Permissions bitmask.
     */
    protected function _aclToHordePerms($acl)
    {
        $hPerms = 0;

        if (strpos($acl, 'w') !== false) {
            $hPerms |= Horde_Perms::EDIT;
        }
        if (strpos($acl, 'r') !== false) {
            $hPerms |= Horde_Perms::READ;
        }
        if (strpos($acl, 'd') !== false) {
            $hPerms |= Horde_Perms::DELETE;
        }
        if (strpos($acl, 'l') !== false) {
            $hPerms |= Horde_Perms::SHOW;
        }

        return $hPerms;
    }

    /**
     * Creates a new Horde_Share and creates the address book
     * on the IMSP server.
     *
     * @param array  The params for the share.
     *
     * @return Horde_Share  The share object.
     * @throws Turba_Exception
     */
    public function createShare($share_id, $params)
    {
        $params['params']['name'] = $this->params['username'];
        if (!isset($params['default']) || $params['default'] !== true) {
            $params['params']['name'] .= '.' . $params['name'];
        }

        $result = Turba::createShare($share_id, $params);
        $imsp_result = Net_IMSP_Utils::createBook($GLOBALS['cfgSources']['imsp'], $params['params']['name']);

        if ($imsp_result instanceof PEAR_Error) {
            throw new Turba_Exception($imsp_result);
        }

        return $result;
    }

    /**
     * Helper function to count the occurances of the ':' * delimiter in group
     * member entries.
     *
     * @param string $in  The group member entry.
     *
     * @return integer  The number of ':' in $in.
     */
    protected function _countDelimiters($in)
    {
        $cnt = $pos = 0;
        $i = -1;
        while (($pos = strpos($in, ':', $pos + 1)) !== false) {
            ++$cnt;
        }

        return $cnt;
    }

    /**
     * Returns the owner for this contact. For an IMSP source, this should be
     * the name of the address book.
     *
     * @return string  TODO
     */
    protected function _getContactOwner()
    {
       return $this->params['name'];
    }

    /**
     * Check if the passed in share is the default share for this source.
     *
     * @see turba/lib/Turba_Driver#checkDefaultShare($share, $srcconfig)
     *
     * @return TODO
     */
    protected function checkDefaultShare($share, $srcConfig)
    {
        $params = @unserialize($share->get('params'));
        if (!isset($params['default'])) {
            $params['default'] = ($params['name'] == $srcConfig['params']['username']);
            $share->set('params', serialize($params));
            $share->save();
        }

        return $params['default'];
    }

}
