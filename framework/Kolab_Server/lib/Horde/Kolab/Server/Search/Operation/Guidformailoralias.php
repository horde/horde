<?php
/**
 * Return all KolabInetOrgPersons with the given mail or alias address.
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
 * Return all KolabInetOrgPersons with the given mail or alias address.
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
class Horde_Kolab_Server_Search_Operation_Guidformailoralias
extends Horde_Kolab_Server_Search_Operation_Restrictkolab
{
    /**
     * Return all KolabInetOrgPersons with the given mail or alias address.
     *
     * @param string $mail  The mail address to search for.
     * @param string $alias The alias address to search for.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForMailOrAlias($mail, $alias)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Or(
            new Horde_Kolab_Server_Query_Element_Equals(
                'Mail', $mail
            ),
            new Horde_Kolab_Server_Query_Element_Equals(
                'Alias', $alias
            )
        );
        return parent::searchRestrictKolab($criteria);
    }
}