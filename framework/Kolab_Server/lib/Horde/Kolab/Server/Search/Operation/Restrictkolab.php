<?php
/**
 * Restrict a search to KolabInetOrgPersons.
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
 * Restrict a search to KolabInetOrgPersons.
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
class Horde_Kolab_Server_Search_Operation_Restrictkolab
extends Horde_Kolab_Server_Search_Operation_Guid
{
    /**
     * Restrict a search to KolabInetOrgPersons.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The search criteria.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchRestrictKolab(
        Horde_Kolab_Server_Query_Element_Interface $criteria
    ) {
        $criteria = new Horde_Kolab_Server_Query_Element_And(
            array(
                new Horde_Kolab_Server_Query_Element_Equals(
                    'objectClass',
                    Horde_Kolab_Server_Object_Kolabinetorgperson::OBJECTCLASS_KOLABINETORGPERSON
                ),
                $criteria
            )
        );
        return parent::searchGuid($criteria);
    }
}