<?php
/**
 * Preferences storage implementation for LDAP servers.
 *
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jon Parise <jon@horde.org>
 * @author   Ben Klang <ben@alkaloid.net>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Heinz Schweiger <heinz@htl-steyr.ac.at>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_Ldap extends Horde_Prefs_Storage_Base
{
    /**
     * Handle for the current LDAP connection.
     *
     * @var Horde_Ldap
     */
    protected $_ldap;

    /**
     * Current DN holding the preferences.
     *
     * @var string
     */
    protected $_prefsDN;

    /**
     * Constructor.
     *
     * @param string $user   The username.
     * @param array $params  Configuration parameters.
     *     - 'ldap': (Horde_Ldap) [REQUIRED] The DB instance.
     *
     * @throws InvalidArgumentException
     */
    public function __construct($user, array $params = array())
    {
        if (!isset($params['ldap'])) {
            throw new InvalidArgumentException('Missing ldap parameter.');
        }

        $this->_ldap = $params['ldap'];
        unset($params['ldap']);

        try {
            $this->_prefsDN = $this->_ldap->findUserDN($user);
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        try {
            // Try do find an existing preference object in an organizational
            // unit under the userDN
            $search = $this->_ldap->search(
                $this->_prefsDN,
                Horde_Ldap_Filter::create('objectclass', 'equals', 'hordePerson'),
                array('attributes' => array('dn'), 'scope' => 'sub')
            );

            if ($search->count() == 1) {
                $this->_prefsDN = $search->shiftEntry()->currentDN();
            }
        } catch (Horde_Ldap_Exception $e) {
        }

        parent::__construct($user, $params);
    }

    /**
     */
    public function get($scope_ob)
    {
        // Preferences are stored as colon-separated name:value pairs.
        // Each pair is stored as its own attribute off of the multi-
        // value attribute named in: $scope_ob->scope . 'Prefs'

        // getEntry() converts attribute indexes to lowercase.
        $field = Horde_String::lower($scope_ob->scope . 'Prefs');

        try {
            $prefs = $this->_ldap->getEntry($this->_prefsDN, array($field));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        if ($prefs->exists($field)) {
            foreach ($prefs->getValue($field, 'all') as $prefstr) {
                // If the string doesn't contain a colon delimiter, skip it.
                if (strpos($prefstr, ':') !== false) {
                    // Split the string into its name:value components.
                    list($name, $value) = explode(':', $prefstr, 2);
                    $scope_ob->set($name, base64_decode($value));
                }
            }
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        // Preferences are stored as colon-separated name:value pairs.
        // Each pair is stored as its own attribute off of the multi-
        // value attribute named in: $scope_ob->scope . 'Prefs'

        // getEntry() converts attribute indexes to lowercase.
        $field = Horde_String::lower($scope_ob->scope . 'Prefs');

        try {
            $prefs = $this->_ldap->getEntry($this->_prefsDN, array($field, 'objectclass'));
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        // Add any missing objectclasses.
        // Entries must have the objectclasses 'top' and 'hordePerson'
        // to successfully store LDAP prefs. Check for both of them,
        // and add them if necessary.
        $objectclasses = $prefs->getValue('objectclass', 'all');
        foreach (array('top', 'hordePerson') as $oc) {
            if (!in_array($oc, $objectclasses)) {
                $prefs->add(array('objectClass' => $oc));
            }
        }

        // Delete dirty preferences if they exists in the current LDAP entry.
        if ($prefs->exists($field)) {
            foreach ($prefs->getValue($field, 'all') as $prefstr) {
                // Split the string into its name:value components.
                list($name, $val) = explode(':', $prefstr, 2);
                // Delete values of dirty preference names
                if ($scope_ob->isDirty($name)) {
                    $prefs->delete(array($field => $prefstr));
                }
            }

            try {
                $prefs->update();
            } catch (Horde_Ldap_Exception $e) {
                throw new Horde_Prefs_Exception($e);
            }
        }

        // Add any dirty values.
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);
            // Null values were deleted above.
            if (!is_null($value)) {
                $prefs->add(array($field => $name . ':' . base64_encode($value)));
            }
        }

        try {
            $prefs->update();
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        if (is_null($scope)) {
            // Clear all scopes.
            $scopes = $this->listScopes();
        } else {
            $scopes = array($scope);
        }

        foreach ($scopes as $s) {
            // getEntry() converts attribute indexes to lowercase.
            $field = Horde_String::lower($s . 'Prefs');

            try {
                $prefs = $this->_ldap->getEntry($this->_prefsDN, array($field));
            } catch (Horde_Ldap_Exception $e) {
                throw new Horde_Prefs_Exception($e);
            }

            if (is_null($pref)) {
                // Clear entire scope.
                $prefs->delete(array($field));
            } elseif ($prefs->exists($field)) {
                // Find preference to clear.
                foreach ($prefs->getValue($field, 'all') as $prefstr) {
                    // Split the string into its name:value components.
                    list($name, $val) = explode(':', $prefstr, 2);
                    if ($name == $pref) {
                        $prefs->delete(array($field => $prefstr));
                    }
                }
            }

            try {
                $prefs->update();
            } catch (Horde_Ldap_Exception $e) {
                throw new Horde_Prefs_Exception($e);
            }
        }
    }

    /**
     * Lists all available scopes.
     *
     * @return array The list of scopes stored in the backend.
     */
    public function listScopes()
    {
        $scopes = array();
        try {
            $prefs = $this->_ldap->search(
                $this->_prefsDN,
                Horde_Ldap_Filter::create('objectclass', 'equals', 'hordePerson'),
                // Attributes associated to objectclass hordePerson.
                array('attributes' => array('@hordePerson'),
                      'scope' => 'base',
                      'attrsonly' => true)
            );
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        if (!$prefs) {
            return $scopes;
        }

        foreach ($prefs->shiftEntry()->attributes() as $attr) {
            // Trim off prefs from attribute name to get scope (e.g. hordePrefs
            // -> horde).
            $scope = str_ireplace("prefs","",$attr);
            // Skip non-prefs attributes like objectclass (no replacement
            // occurred above).
            if ($attr != $scope) {
                $scopes[] = $scope;
            }
        }

        return $scopes;
    }
}
