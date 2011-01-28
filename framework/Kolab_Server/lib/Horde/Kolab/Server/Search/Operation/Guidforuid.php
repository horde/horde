<?php
/**
 * Return all KolabInetOrgPersons with the given uid.
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
 * Return all KolabInetOrgPersons with the given uid.
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
class Horde_Kolab_Server_Search_Operation_Guidforuid
extends Horde_Kolab_Server_Search_Operation_Restrictkolab
{
    /**
     * Return all KolabInetOrgPersons with the given uid.
     *
     * @param string $uid The uid to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForUid($uid)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Equals(
            'uid', $uid
        );
        return parent::searchRestrictKolab($criteria);
    }
}