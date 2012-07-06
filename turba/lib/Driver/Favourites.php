<?php
/**
 * Read-only Turba directory driver implementation for favourite
 * recipients. Relies on the contacts/favouriteRecipients API method.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Driver_Favourites extends Turba_Driver
{
    /**
     * Checks if the current user has the requested permissions on this
     * source.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  True if the user has permission, otherwise false.
     */
     public function hasPermission($perm)
     {
         switch ($perm) {
         case Horde_Perms::DELETE:
         case Horde_Perms::EDIT:
             return false;

         default:
             return true;
         }
     }

    /**
     * Always returns true because the driver is read-only and there is
     * nothing to remove.
     *
     * @param string $user  The user's data to remove.
     *
     * @return boolean  Always true.
     */
    public function removeUserData($user)
    {
        return true;
    }

    /**
     * Searches the favourites list with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     * @param array $blobFields  A list of fields that contain binary data.
     *
     * @return array  Hash containing the search results.
     * @throws Turba_Exception
     */
    protected function _search(array $criteria, array $fields, array $blobFields = array())
    {
        $results = array();

        foreach ($this->_getAddressBook() as $key => $contact) {
            if (!count($criteria)) {
                $results[$key] = $contact;
                continue;
            }
            foreach ($criteria as $op => $vals) {
                if ($op == 'AND') {
                    if (!count($vals)) {
                        $found = false;
                    } else {
                        $found = true;
                        foreach ($vals as $val) {
                            if (!$this->_match($contact, $val)) {
                                $found = false;
                                break;
                            }
                        }
                    }
                } elseif ($op == 'OR') {
                    $found = false;
                    foreach ($vals as $val) {
                        if ($this->_match($contact, $val)) {
                            $found = true;
                            break;
                        }
                    }
                } else {
                    $found = false;
                }
            }
            if ($found) {
                $results[$key] = $contact;
            }
        }

        return $results;
    }

    /**
     * Returns whether a contact matches some criteria.
     *
     * @param array $contact  A contact hash.
     * @param array $val      Some matching criterion, see _search().
     *
     * @return boolean  True if the contact matches.
     */
    protected function _match($contact, $val)
    {
        if (!isset($contact[$val['field']])) {
            return false;
        }
        switch ($val['op']) {
        case '=':
            return (string)$contact[$val['field']] == (string)$val['test'];
        case 'LIKE':
            return empty($val['test']) ||
                stristr($contact[$val['field']], $val['test']) !== false;
        }
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
     *
     * @throws Turba_Exception
     */
    protected function _getAddressBook()
    {
        global $registry;

        if (!$registry->hasMethod('contacts/favouriteRecipients')) {
            throw new Turba_Exception(_("No source for favourite recipients exists."));
        }

        try {
            $addresses = $registry->call('contacts/favouriteRecipients', array($this->_params['limit']));
        } catch (Horde_Exception $e) {
            if ($e->getCode() == Horde_Registry::AUTH_FAILURE ||
                $e->getCode() == Horde_Registry::NOT_ACTIVE ||
                $e->getCode() == Horde_Registry::PERMISSION_DENIED) {
                return array();
            }
            throw new Turba_Exception($e);
        } catch (Exception $e) {
            throw new Turba_Exception($e);
        }

        $addressbook = array();
        foreach ($addresses as $address) {
            $addressbook[$address] = array('email' => $address);
        }

        return $addressbook;
    }

}
