<?php
/**
 * Read-only Turba_Driver implementation for creating a Horde_Group based
 * address book.
 *
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Turba
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
    public function __construct($params)
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
    function hasPermission($perm)
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
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    function _search($criteria, $fields)
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
        return $results;
    }

    /**
     * Read the data from the address book.
     * Again, this method taken from the favorites driver.
     *
     * @param array $criteria  Search criteria.
     * @param string $id       Data identifier.
     * @param array $fields    List of fields to return.
     *
     * @return  Hash containing the search results.
     */
    function _read($criteria, $ids, $fields)
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

    function _getAddressBook()
    {
        $groups = Horde_Group::singleton();
        $members = $groups->listAllUsers($this->_gid);
        $addressbook = array();
        foreach ($members as $member) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity($member);
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
