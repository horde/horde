<?php
/**
 * A Kolab object of type administrator.
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
 * This class provides methods to deal with administrator object types.
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
class Horde_Kolab_Server_Object_Kolab_Adminrole extends Horde_Kolab_Server_Object_Kolabinetorgperson
{

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        self::ATTRIBUTE_ID,
        self::ATTRIBUTE_LNFN,
    );

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var string
     */
    protected $required_group;

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return '(&(cn=*)(objectClass=inetOrgPerson)(!(uid=manager))(sn=*))';
    }

    /**
     * Convert the object attributes to a hash.
     *
     * @param string $attrs The attributes to return.
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash($attrs = null)
    {
        if (!isset($attrs)) {
            $attrs = array(
                self::ATTRIBUTE_SID,
                self::ATTRIBUTE_LNFN,
            );
        }
        return parent::toHash($attrs);
    }

    /**
     * Saves object information.
     *
     * @param array $info The information about the object.
     *
     * @return boolean|PEAR_Error True on success.
     */
    public function save($info)
    {
        if (!isset($info['cn'])) {
            if (!isset($info['sn']) || !isset($info['givenName'])) {
                return PEAR::raiseError('Either the last name or the given name is missing!');
            } else {
                $info['cn'] = $this->generateId($info);
            }
        }

        $admins_uid = sprintf('%s,%s', $this->required_group,
                              $this->server->getBaseUid());

        $admin_group = $this->server->fetch($admins_uid, 'Horde_Kolab_Server_Object_Kolabgroupofnames');
        if ($admin_group instanceOf PEAR_Error || !$admin_group->exists()) {

            $members = array($this->uid);

            //FIXME: This is not okay and also contains too much LDAP knowledge
            $parts           = split(',', $this->required_group);
            list($groupname) = sscanf($parts[0], 'cn=%s');

            $result = $this->server->add(array(self::ATTRIBUTE_CN => $groupname,
                                               'type' => 'Horde_Kolab_Server_Object_Kolabgroupofnames',
                                               Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_MEMBER => $members,
                                               Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_VISIBILITY => false));
            if ($result instanceOf PEAR_Error) {
                return $result;
            }
        } else {
            $result = $admin_group->isMember($this->uid);
            if ($result instanceOf PEAR_Error) {
                return $result;
            }
            if ($result === false) {
                $members   = $admin_group->getMembers();
                $members[] = $this->uid;
                $admin_group->save(array(self::ATTRIBUTE_MEMBER => $members));
            }
        }
        return parent::save($info);
    }

}
