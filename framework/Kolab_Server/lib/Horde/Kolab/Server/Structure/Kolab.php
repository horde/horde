<?php
/**
 * The driver for handling the Kolab user database structure.
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
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Structure_Kolab extends Horde_Kolab_Server_Structure_Ldap
{
    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
        return array(
            'Horde_Kolab_Server_Object',
            'Horde_Kolab_Server_Object_Groupofnames',
            'Horde_Kolab_Server_Object_Person',
            'Horde_Kolab_Server_Object_Organizationalperson',
            'Horde_Kolab_Server_Object_Inetorgperson',
            'Horde_Kolab_Server_Object_Kolab',
            'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'Horde_Kolab_Server_Object_Kolabgermanbankarrangement',
            'Horde_Kolab_Server_Object_Kolabpop3account',
            'Horde_Kolab_Server_Object_Kolabgroupofnames',
            'Horde_Kolab_Server_Object_Kolabsharedfolder',
            'Horde_Kolab_Server_Object_Kolab_Address',
            'Horde_Kolab_Server_Object_Kolab_Administrator',
            'Horde_Kolab_Server_Object_Kolab_Distlist',
            'Horde_Kolab_Server_Object_Kolab_Domainmaintainer',
            'Horde_Kolab_Server_Object_Kolab_Maintainer',
            'Horde_Kolab_Server_Object_Kolab_User',
        );
    }

    /**
     * Returns the set of search operations supported by this object type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        $searches = array(
            'Horde_Kolab_Server_Search_Operation_Guid',
            'Horde_Kolab_Server_Search_Operation_Attributes',
            'Horde_Kolab_Server_Search_Operation_Children',
            'Horde_Kolab_Server_Search_Operation_Guidforcn',
            'Horde_Kolab_Server_Search_Operation_Guidforkolabusers',
            'Horde_Kolab_Server_Search_Operation_Guidforuid',
            'Horde_Kolab_Server_Search_Operation_Guidformail',
            'Horde_Kolab_Server_Search_Operation_Guidforuidormail',
            'Horde_Kolab_Server_Search_Operation_Guidforalias',
            'Horde_Kolab_Server_Search_Operation_Guidformailoralias',
            'Horde_Kolab_Server_Search_Operation_Guidforuidormailoralias',
            'Horde_Kolab_Server_Search_Operation_Mailforuidormail',
            'Horde_Kolab_Server_Search_Operation_Addressesforuidormail',
            'Horde_Kolab_Server_Search_Operation_Groupsformember',
        );
        return $searches;
    }

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $guid The GUID of the object to examine.
     * @param array  $ocs  The object classes of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    protected function _determineType($guid, array $ocs)
    {
        // Not a user type?
        if (!in_array('kolabinetorgperson', $ocs)) {
            // Is it a group?
            if (in_array('kolabgroupofnames', $ocs)) {
                return 'Horde_Kolab_Server_Object_Kolabgroupofnames';
            }
            // Is it an external pop3 account?
            if (in_array('kolabexternalpop3account', $ocs)) {
                return 'Horde_Kolab_Server_Object_Kolabpop3account';
            }
            // Is it a shared Folder?
            if (in_array('kolabsharedfolder', $ocs)) {
                return 'Horde_Kolab_Server_Object_Kolabsharedfolder';
            }
            return parent::_determineType($guid, $ocs);
        }

        $groups = $this->getComposite()->search->searchGroupsForMember($guid);
        if (!empty($groups)) {
            $base = $this->getComposite()->server->getBaseGuid();
            if (in_array('cn=admin,cn=internal,' . $base, $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Administrator';
            }
            if (in_array('cn=maintainer,cn=internal,' . $base,
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Maintainer';
            }
            if (in_array('cn=domain-maintainer,cn=internal,' . $base,
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer';
            }
        }

        if (strpos($guid, 'cn=external') !== false) {
            return 'Horde_Kolab_Server_Object_Kolab_Address';
        }

        return 'Horde_Kolab_Server_Object_Kolab_User';
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerGuid($type, $id, array $info)
    {
        switch ($type) {
        case 'Horde_Kolab_Server_Object_Kolab_User':
            if (empty($info['user_type'])) {
                return parent::generateServerGuid($type, $id, $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_INTERNAL) {
                return parent::generateServerGuid($type,
                                                  sprintf('%s,cn=internal', $id),
                                                  $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_GROUP) {
                return parent::generateServerGuid($type,
                                                  sprintf('%s,cn=groups', $id),
                                                  $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_RESOURCE) {
                return parent::generateServerGuid($type,
                                                  sprintf('%s,cn=resources', $id),
                                                  $info);
            } else {
                return parent::generateServerGuid($type, $id, $info);
            }
        case 'Horde_Kolab_Server_Object_Kolab_Address':
            return parent::generateServerGuid($type,
                                              sprintf('%s,cn=external', $id),
                                              $info);
        case 'Horde_Kolab_Server_Object_Kolabgroupofnames':
        case 'Horde_Kolab_Server_Object_Kolab_Distlist':
            if (!isset($info['visible']) || !empty($info['visible'])) {
                return parent::generateServerGuid($type, $id, $info);
            } else {
                return parent::generateServerGuid($type,
                                                  sprintf('%s,cn=internal', $id),
                                                  $info);
            }
        case 'Horde_Kolab_Server_Object_Kolabsharedfolder':
        case 'Horde_Kolab_Server_Object_Kolab_Administrator':
        case 'Horde_Kolab_Server_Object_Kolab_Maintainer':
        case 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer':
        default:
            return parent::generateServerGuid($type, $id, $info);
        }
    }
}
