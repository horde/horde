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
class Horde_Kolab_Server_Object_Kolab_Adminrole extends Horde_Kolab_Server_Object_Kolabinetorgperson
{

    static public $init_attributes = array(
    );

    /**
     * The group the UID must be member of so that this object really
     * matches this class type. This may not include the root UID.
     *
     * @var string
     */
    public $required_group;

    /**
     * Return the filter string to retrieve this object type.
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        if (isset($conf['kolab']['server']['params']['admin'][self::ATTRIBUTE_SID])) {
            $manager = $conf['kolab']['server']['params']['admin'][self::ATTRIBUTE_SID];
        } else {
            $manager = 'manager';
        }

        $criteria = array('AND' => array(
                              array('field' => self::ATTRIBUTE_CN,
                                    'op'    => 'any'),
                              array('field' => self::ATTRIBUTE_SN,
                                    'op'    => 'any'),
                              array('field' => self::ATTRIBUTE_OC,
                                    'op'    => '=',
                                    'test'  => self::OBJECTCLASS_INETORGPERSON),
                              array('NOT' => array(
                                        array('field' => self::ATTRIBUTE_SID,
                                              'op'    => '=',
                                              'test'  => $manager),
                                    ),
                              ),
                          ),
        );
        return $criteria;
    }

    /**
     * Saves object information.
     *
     * @param array $info The information about the object.
     *
     * @return boolean|PEAR_Error True on success.
     */
    public function save(array $info)
    {
        $admin_group = new Horde_Kolab_Server_Object_Kolabgroupofnames($this->server, null, $this->required_group);

        $save_result = parent::save($info);

        if (!$admin_group->exists()) {
            $data = array_merge($this->required_group,
                                array(Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_MEMBER => array($this->uid)));
        } else {
            $result = $admin_group->isMember($this->uid);
            if ($result === false) {
                $members   = $admin_group->getMembers();
                $members[] = $this->uid;
                $data      = array(Horde_Kolab_Server_Object_Kolabgroupofnames::ATTRIBUTE_MEMBER => $members);
            } else {
                $data = null;
            }
        }
        if (!empty($data)) {
            return $admin_group->save($data);
        }
        return $save_result;
    }
}
