<?php
/**
 * Read-only Turba directory driver for Facebook friends. Requires a Horde
 * application to be setup on Facebook and configured in horde/config/conf.php.
 * This driver based on the favourites driver.
 *
 * Of limited utility since email addresses are not retrievable via the Facebook
 * API, unless the user allows the Horde application to access it - and even
 * then, it's a proxied email address.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 */
class Turba_Driver_Facebook extends Turba_Driver
{
    private $_facebook;

    /**
     */
    function _init()
    {
        return true;
    }

    /**
     * Checks if the current user has the requested permissions on this
     * source.
     *
     * @param integer $perm  The permission to check for.
     *
     * @return boolean  True if the user has permission, otherwise false.
     */
     function hasPermission($perm)
     {
         switch ($perm) {
             case Horde_Perms::EDIT: return false;
             case Horde_Perms::DELETE: return false;
             default: return true;
         }
     }

    /**
     * Searches the favourites list with the given criteria and returns a
     * filtered list of results. If the criteria parameter is an empty array,
     * all records will be returned.
     *
     * @param array $criteria  Array containing the search criteria.
     * @param array $fields    List of fields to return.
     *
     * @return array  Hash containing the search results.
     */
    function _search($criteria, $fields)
    {
        $results = array();
        $results = $this->_getAddressBook($fields);
        if (is_a($results, 'PEAR_Error')) {
            return $results;
        }

        foreach ($results as $key => $contact) {
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
     * Reads the given data from the API method and returns the result's
     * fields.
     *
     * @param array $criteria  Search criteria.
     * @param string $id       Data identifier.
     * @param array $fields    List of fields to return.
     *
     * @return  Hash containing the search results.
     */
    function _read($criteria, $ids, $owner, $fields)
    {
        $results = $this->_getEntry($ids, $fields);
        return $results;
    }

    function _getEntry($keys, $fields)
    {
        if (!is_a($facebook = $this->_getFacebook(), 'PEAR_Error')) {
            $fields = implode(', ', $fields);
            $fql = 'SELECT ' . $fields . ' FROM user WHERE uid IN (' . implode(', ', $keys) . ')';

            try {
                $results = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                $error = PEAR::raiseError($e->getMessage(), $e->getCode());
                Horde::logMessage($error, 'ERR');

                return $error;
            }

            return $results;
        } else {

            return $facebook;
        }
    }

    function _getAddressBook($fields = array())
    {
        if (!is_a($facebook = $this->_getFacebook(), 'PEAR_Error')) {
            $fields = implode(', ', $fields);
            // For now, just try a fql query with name and email.
            $fql = 'SELECT ' . $fields . ' FROM user WHERE uid IN ('
                . 'SELECT uid2 FROM friend WHERE uid1=' . $facebook->auth->getUser() . ')';

            try {
                $results = $facebook->fql->run($fql);
            } catch (Horde_Service_Facebook_Exception $e) {
                $error = PEAR::raiseError($e->getMessage(), $e->getCode());
                Horde::logMessage($error, 'ERR');
                return array();
            }
            $addressbook = array();
            foreach ($results as $result) {
                if (!empty($result['birthday'])) {
                    // Make sure the birthdate is in a standard format that
                    // listDateObjects will understand.
                    $bday = new Horde_Date($result['birthday']);
                    $result['birthday'] = $bday->format('Y-m-d');
                }
                $addressbook[$result['uid']] = $result;
            }

            return $addressbook;
        } else {
            return $facebook;
        }
    }

    function _getFacebook()
    {
        global $conf, $prefs;
        if (!$conf['facebook']['enabled']) {
            return PEAR::raiseError(_("No Facebook integration exists."));
        }

        if (empty($this->_facebook)) {
            $context = array('http_client' => new Horde_Http_Client(),
                             'http_request' => $GLOBALS['injector']->getInstance('Horde_Controller_Request'));
            $this->_facebook = new Horde_Service_Facebook($conf['facebook']['key'],
                                                   $conf['facebook']['secret'],
                                                   $context);

            $session = unserialize($prefs->getValue('facebook'));
            if (!$session || !isset($session['uid']) || !isset($session['sid'])) {
                return PEAR::raiseError(_("You have to connect to Facebook in your address book preferences."));
            }
            $this->_facebook->auth->setUser($session['uid'], $session['sid'], 0);
        }

        return $this->_facebook;
    }

}
