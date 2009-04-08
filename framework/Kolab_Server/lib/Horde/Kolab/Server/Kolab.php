<?php
/**
 * The driver for accessing the Kolab user database stored in LDAP.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with Kolab objects stored in
 * the standard Kolab LDAP db.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Kolab extends Horde_Kolab_Server_Ldap
{
    /**
     * Returns the set of objects supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    static public function getSupportedObjects()
    {
        $objects = array(
            'Horde_Kolab_Server_Object',
            'Horde_Kolab_Server_Object_Groupofnames',
            'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'Horde_Kolab_Server_Object_Kolabgroupofnames',
        );
        return $objects;
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $dn The DN of the object to examine.
     *
     * @return int The corresponding Kolab object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($dn)
    {
        $oc = $this->getObjectClasses($dn);
        // Not a user type?
        if (!in_array('kolabinetorgperson', $oc)) {
            // Is it a group?
            if (in_array('kolabgroupofnames', $oc)) {
                return 'Horde_Kolab_Server_Object_Kolabgroupofnames';
            }
            // Is it a shared Folder?
            if (in_array('kolabsharedfolder', $oc)) {
                return 'Horde_Kolab_Server_Object_Kolabsharedfolder';
            }
            return parent::determineType($dn);
        }

        $groups = $this->getGroups($dn);
        if (!empty($groups)) {
            if (in_array('cn=admin,cn=internal,' . $this->getBaseUid(), $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Administrator';
            }
            if (in_array('cn=maintainer,cn=internal,' . $this->getBaseUid(),
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Maintainer';
            }
            if (in_array('cn=domain-maintainer,cn=internal,' . $this->getBaseUid(),
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer';
            }
        }

        if (strpos($dn, 'cn=external') !== false) {
            return 'Horde_Kolab_Server_Object_Kolab_Address';
        }

        return 'Horde_Kolab_Server_Object_Kolab_User';
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The DN.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info)
    {
        switch ($type) {
        case 'Horde_Kolab_Server_Object_Kolab_User':
            if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_INTERNAL) {
                return sprintf('%s,cn=internal,%s', $id, $this->getBaseUid());
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_GROUP) {
                return sprintf('%s,cn=groups,%s', $id, $this->getBaseUid());
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_RESOURCE) {
                return sprintf('%s,cn=resources,%s', $id, $this->getBaseUid());
            } else {
                return parent::generateServerUid($type, $id, $info);
            }
        case 'Horde_Kolab_Server_Object_Kolab_Address':
            return sprintf('%s,cn=external,%s', $id, $this->getBaseUid());
        case 'Horde_Kolab_Server_Object_Kolabgroupofnames':
        case 'Horde_Kolab_Server_Object_Kolab_Distlist':
            if (!isset($info['visible']) || !empty($info['visible'])) {
                return parent::generateServerUid($type, $id, $info);
            } else {
                return sprintf('%s,cn=internal,%s', $id, $this->getBaseUid());
            }
        case 'Horde_Kolab_Server_Object_Kolabsharedfolder':
        case 'Horde_Kolab_Server_Object_Kolab_Administrator':
        case 'Horde_Kolab_Server_Object_Kolab_Maintainer':
        case 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer':
        default:
            return parent::generateServerUid($type, $id, $info);
        }
    }
}
