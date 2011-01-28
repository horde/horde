<?php
/**
 * Basic GUID search.
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
 * Basic GUID search.
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
class Horde_Kolab_Server_Search_Operation_Children
extends Horde_Kolab_Server_Search_Operation_Base
{
    /**
     * Perform the search.
     *
     * @param string $parent_guid The guid of the parent.
     * @param string $objectclass The type of children to return.
     *
     * @return mixed The search result.
     */
    public function searchChildren($parent_guid, $objectclass)
    {
        $criteria = new Horde_Kolab_Server_Query_Element_Equals(
            'Objectclass', $objectclass
        );
        $params = array(
            'attributes' => Horde_Kolab_Server_Object_Top::ATTRIBUTE_GUID
        );
        $data = $this->_composite->server->findBelow(
            $criteria, $parent, $params
        );
        return self::guidFromResult($data);
    }
}