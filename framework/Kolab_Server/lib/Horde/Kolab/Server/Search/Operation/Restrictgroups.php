<?php
/**
 * Restrict a search to groupOfNames.
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
 * Restrict a search to groupOfNames.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Search_Operation_Restrictgroups
extends Horde_Kolab_Server_Search_Operation_Guid
{
    /**
     * Restrict a search to groupOfNames.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The search criteria.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchRestrictGroups(
        Horde_Kolab_Server_Query_Element_Interface $criteria
    ) {
        $criteria = new Horde_Kolab_Server_Query_Element_And(
            array(
                new Horde_Kolab_Server_Query_Element_Equals(
                    'objectClass',
                    Horde_Kolab_Server_Object_Groupofnames::OBJECTCLASS_GROUPOFNAMES
                ),
                $criteria
            )
        );
        return parent::searchGuid($criteria);
    }
}