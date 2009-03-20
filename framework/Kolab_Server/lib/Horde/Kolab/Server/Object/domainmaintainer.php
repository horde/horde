<?php
/**
 * A Kolab domain maintainer.
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
 * This class provides methods associated to Kolab domain maintainers.
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
class Horde_Kolab_Server_Object_domainmaintainer extends Horde_Kolab_Server_Object_adminrole
{

    /**
     * Attributes derived from the LDAP values.
     *
     * @var array
     */
    public $derived_attributes = array(
        Horde_Kolab_Server_Object::ATTRIBUTE_ID,
        Horde_Kolab_Server_Object::ATTRIBUTE_LNFN,
        Horde_Kolab_Server_Object::ATTRIBUTE_DOMAIN,
    );

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var string
     */
    protected $required_group = 'cn=domain-maintainer,cn=internal';

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
                Horde_Kolab_Server_Object::ATTRIBUTE_SID,
                Horde_Kolab_Server_Object::ATTRIBUTE_LNFN,
                Horde_Kolab_Server_Object::ATTRIBUTE_DOMAIN,
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
        foreach ($info[Horde_Kolab_Server_Object::ATTRIBUTE_DOMAIN] as $domain) {
            $domain_uid = sprintf('cn=%s,cn=domain,cn=internal,%s',
                                  $domain, $this->db->getBaseUid());

            //FIXME: This should be made easier by the group object

            $domain_group = $this->db->fetch($domain_uid, 'Horde_Kolab_Server_Object_group');
            if (is_a($domain_group, 'PEAR_Error')) {
                return $domain_group;
            }
            if (!$domain_group->exists()) {
                $members = array($this->uid);
                $domain_group->save(array(Horde_Kolab_Server_Object::ATTRIBUTE_CN => $domain,
                                          Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER => $members));
            } else {
                $result = $domain_group->isMember($this->uid);
                if (is_a($result, 'PEAR_Error')) {
                    return $result;
                }
                if ($result === false) {
                    $members   = $domain_group->getMembers();
                    $members[] = $this->uid;
                    $domain_group->save(array(Horde_Kolab_Server_Object::ATTRIBUTE_MEMBER => $members));
                }
            }
        }
        return parent::save($info);
    }

}
