<?php
/**
 * Read-only Turba_Driver implementation for creating a Horde_Group based
 * address book.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Driver_Group extends Turba_Driver
{
    /**
     * Constructor function.
     *
     * @param array $params  Array of parameters for this driver.
     *                       Basically, just passes the group id.
     *
     */
    public function __construct($name = '', $params)
    {
         $this->_gid = $params['gid'];
    }

    /**
     * Checks if the current user has the requested permissions on this
     * source.  This source is always read only.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  True if the user has permission, otherwise false.
     */
    public function hasPermission($perm)
    {
        switch ($perm) {
        case Horde_Perms::EDIT:
        case Horde_Perms::DELETE:
            return false;

        default:
            return true;
        }
    }

    /**
     * Searches the group list with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * This method 'borrowed' from the favorites driver.
     *
     * @param array $criteria    Array containing the search criteria.
     * @param array $fields      List of fields to return.
     * @param array $blobFields  A list of fields that contain binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array(), $count_only = false)
    {
        $results = array();

        foreach ($this->_getAddressBook() as $key => $contact) {
            $found = !isset($criteria['OR']);
            foreach ($criteria as $op => $vals) {
                if ($op == 'AND') {
                    foreach ($vals as $val) {
                        if (isset($contact[$val['field']])) {
                            switch ($val['op']) {
                            case 'LIKE':
                                if (stristr($contact[$val['field']], $val['test']) === false) {
                                    continue 4;
                                }
                                $found = true;
                                break;
                            }
                        }
                    }
                } elseif ($op == 'OR') {
                    foreach ($vals as $val) {
                        if (isset($contact[$val['field']])) {
                            switch ($val['op']) {
                            case 'LIKE':
                                if (empty($val['test']) ||
                                    stristr($contact[$val['field']], $val['test']) !== false) {
                                    $found = true;
                                    break 3;
                                }
                            }
                        }
                    }
                }
            }
            if ($found) {
                $results[$key] = $contact;
            }
        }

        return $count_only ? count($results) : $results;
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
     * TODO
     */
    protected function _getAddressBook()
    {
        $groups = $GLOBALS['injector']->getInstance('Horde_Group');
        $members = $groups->listUsers($this->_gid);
        $addressbook = array();
        foreach ($members as $member) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($member);
            $name = $identity->getValue('fullname');
            $email = $identity->getValue('from_addr');
            // We use the email as the key since we could have multiple users
            // with the same fullname, so no email = no entry in address book.
            if (!empty($email)) {
                $addressbook[$email] = array(
                    'name' => ((!empty($name) ? $name : $member)),
                    'email' => $identity->getValue('from_addr')
                );
            }
        }

        return $addressbook;
    }

}
