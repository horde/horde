<?php
/**
 * Identify the GUID for the objects found with the given common name.
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
 * Identify the GUID for the objects found with the given common name.
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
class Horde_Kolab_Server_Search_Operation_Guidforcn
extends Horde_Kolab_Server_Search_Operation_Guid
{
    /**
     * Identify the GUID for the objects found with the given common name.
     *
     * @param string $cn Search for objects with this common name.
     *
     * @return array The GUID(s).
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function searchGuidForCn($cn)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Equals(
            'cn', $cn
        );
        return parent::searchGuid($criteria);
    }
}