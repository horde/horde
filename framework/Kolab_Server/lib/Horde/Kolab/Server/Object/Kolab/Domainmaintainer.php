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
class Horde_Kolab_Server_Object_Kolab_Domainmaintainer extends Horde_Kolab_Server_Object_Kolab_Adminrole
{

    const ATTRIBUTE_DOMAIN = 'domain';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_DOMAIN,
        ),
    );

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var array
     */
    public $required_group = array(self::ATTRIBUTE_CN => 'domain-maintainer',
                                      Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_VISIBILITY => false);

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
                self::ATTRIBUTE_DOMAIN,
            );
        }
        return parent::toHash($attrs);
    }

    /**
     * Distill the server side object information to save.
     *
     * @param array $info The information about the object.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the given information contains errors.
     */
    public function prepareObjectInformation(array &$info)
    {
        foreach ($info[self::ATTRIBUTE_DOMAIN] as $domain) {
            $domain_uid = sprintf('cn=%s,cn=domain,cn=internal,%s',
                                  $domain, $this->server->getBaseUid());

            //@todo: This should be made easier by the group object

            $domain_group = $this->server->fetch($domain_uid, 'Horde_Kolab_Server_Object_Kolabgroupofnames');
            if ($domain_group instanceOf PEAR_Error) {
                return $domain_group;
            }
            if (!$domain_group->exists()) {
                $members = array($this->uid);
                $domain_group->save(array(self::ATTRIBUTE_CN => $domain,
                                          Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_MEMBER => $members));
            } else {
                $result = $domain_group->isMember($this->uid);
                if ($result instanceOf PEAR_Error) {
                    return $result;
                }
                if ($result === false) {
                    $members   = $domain_group->getMembers();
                    $members[] = $this->uid;
                    $domain_group->save(array(Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_MEMBER => $members));
                }
            }
        }
        parent::prepareObjectInformation(&$info);
    }

}
