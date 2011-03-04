<?php
/**
 * This class provides a Kolab driver for the Horde group system.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Group
 */
class Horde_Group_Kolab extends Horde_Group_Ldap
{
    /**
     * Constructor.
     *
     * @throws Horde_Group_Exception
     */
    /*
    public function __construct($params)
    {
        $this->_params = array(
            'hostspec' => $GLOBALS['conf']['kolab']['ldap']['server'],
            'basedn' => $GLOBALS['conf']['kolab']['ldap']['basedn'],
            'binddn' => $GLOBALS['conf']['kolab']['ldap']['phpdn'],
            'password' => $GLOBALS['conf']['kolab']['ldap']['phppw'],
            'version' => 3,
            'gid' => 'cn',
            'memberuid' => 'member',
            'attrisdn' => true,
            'filter_type' => 'objectclass',
            'objectclass' => 'kolabGroupOfNames',
            'newgroup_objectclass' => 'kolabGroupOfNames'
        );

        $this->_filter = 'objectclass=' . $this->_params['objectclass'];
    }
    */

    /**
     * Returns whether the group backend is read-only.
     *
     * @return boolean
     */
    public function readOnly()
    {
        return true;
    }

    /**
     * Returns a list of groups a user belongs to.
     *
     * @param string $user  A user name.
     *
     * @return array  A list of groups, with IDs as keys and names as values.
     * @throws Horde_Group_Exception
     */
    public function listGroups($user)
    {
        return parent::listGroups($this->_dnForMail($user));
    }

    /**
     * Tries to find a DN for a given kolab mail address.
     *
     * @param string $mail  The mail address to search for.
     *
     * @return string  The corresponding dn or false.
     * @throws Horde_Group_Exception
     */
    protected function _dnForMail($mail)
    {
        try {
            $filter = Horde_Ldap_Filter::combine(
                'and',
                array(Horde_Ldap_Filter::create('objectclass', 'equals', 'kolabInetOrgPerson'),
                      Horde_Ldap_Filter::create('mail', 'equals', $mail)));
            $search = $this->_ldap->search($this->_params['basedn'], $filter, array('dn'));
            if ($search->count()) {
                return $search->shiftEntry()->dn();
            }
        } catch (Horde_Ldap_Exception $e) {
            throw new Horde_Group_Exception($e);
        }

        throw new Horde_Group_Exception(sprintf('Error searching for user with the email address "%s"', $mail));
    }
}
