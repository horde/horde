<?php
/**
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Chavet <ben@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_KolabObject extends LDAP_Group
{
    /**
     * Constructor.
     *
     * @param string $name    The name of this group.
     * @param string $parent  The dn of the parent of this group.
     */
    public function __construct($name, $parent = null)
    {
        $this->setName($name);
    }

    /**
     * Fetch the ID of this group
     *
     * @return string The group's ID
     */
    public function getId()
    {
        return $this->getDn();
    }

    /**
     * Save any changes to this object to the backend permanently.
     *
     * @throws Horde_Group_Exception
     */
    public function save()
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Adds a user to this group, and makes sure that the backend is
     * updated as well.
     *
     * @param string $username The user to add.
     *
     * @throws Horde_Group_Exception
     */
    public function addUser($username, $update = true)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Removes a user from this group, and makes sure that the backend
     * is updated as well.
     *
     * @param string $username The user to remove.
     *
     * @throws Horde_Group_Exception
     */
    public function removeUser($username, $update = true)
    {
        throw new Horde_Group_Exception('Unsupported.');
    }

    /**
     * Get all the users recently added or removed from the group.
     */
    public function getAuditLog()
    {
        return array();
    }

    /**
     * Clears the audit log. To be called after group update.
     */
    public function clearAuditLog()
    {
    }

    /**
     * Sets the name of this object.
     *
     * @param string $name  The name to set this object's name to.
     */
    public function getDn()
    {
        return $this->name . ',' . $GLOBALS['conf']['kolab']['ldap']['basedn'];
    }

    /**
     * Take in a list of attributes from the backend and map it to our
     * internal data array.
     *
     * @param array $attributes  The list of attributes from the backend.
     */
    protected function _fromAttributes($attributes = array())
    {
        $this->data['users'] = array();
        foreach ($attributes as $key => $value) {
            if (Horde_String::lower($key) == 'member') {
                if (is_array($value)) {
                    foreach ($value as $user) {
                        $pattern = '/^cn=([^,]+).*$/';
                        $results = array();
                        preg_match($pattern, $user, $results);
                        if (isset($results[1])) {
                            $user = $results[1];
                        }
                        $this->data['users'][$user] = '1';
                    }
                } else {
                    $pattern = '/^cn=([^,]+).*$/';
                    $results = array();
                    preg_match($pattern, $value, $results);
                    if (isset($results[1])) {
                        $value = $results[1];
                    }
                    $this->data['users'][$value] = '1';
                }
            } elseif ($key == 'mail') {
                $this->data['email'] = $value;
            } else {
                $this->data[$key] = $value;
            }
        }
    }

    /**
     * Map this object's attributes from the data array into a format that
     * can be stored in an LDAP entry.
     *
     * @return array  The entry array.
     */
    protected function _toAttributes()
    {
        $attributes = array();
        foreach ($this->data as $key => $value) {
            if ($key == 'users') {
                foreach ($value as $user => $membership) {
                    $user = 'cn=' . $user . ',' . $GLOBALS['conf']['kolab']['ldap']['basedn'];
                    $attributes['member'][] = $user;
                }
            } elseif ($key == 'email') {
                if (!empty($value)) {
                    $attributes['mail'] = $value;
                }
            } elseif ($key != 'dn' && $key != 'member') {
                $attributes[$key] = !empty($value) ? $value : ' ';
            }
        }

        return $attributes;
    }

}
