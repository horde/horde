<?php
/**
 * Return all KolabInetOrgPersons with the given uid, mail or alias address.
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
 * Return all KolabInetOrgPersons with the given uid, mail or alias address.
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
class Horde_Kolab_Server_Search_Operation_Guidforuidormailoralias
extends Horde_Kolab_Server_Search_Operation_Restrictkolab
{
    /**
     * Return all KolabInetOrgPersons with the given uid, mail or alias address.
     *
     * @param string $id The uid or mail address or alias address to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForUidOrMailOrAlias($id)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Or(
            array(
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Uid', $id
                ),
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Mail', $id
                ),
                new Horde_Kolab_Server_Query_Element_Equals(
                    'Alias', $id
                )
            )
        );
        return parent::searchRestrictKolab($criteria);
    }
}