<?php
/**
 * Return the GUIDs of all KolabInetOrgPersons.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Return the GUIDs of all KolabInetOrgPersons.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Search_Operation_Guidforkolabusers
extends Horde_Kolab_Server_Search_Operation_Guid
{
    /**
     * Return the GUIDs of all KolabInetOrgPersons.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForKolabUsers()
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Equals(
            'Objectclass',
            Horde_Kolab_Server_Object_Kolabinetorgperson::OBJECTCLASS_KOLABINETORGPERSON
        );
        return parent::searchGuid($criteria);
    }
}