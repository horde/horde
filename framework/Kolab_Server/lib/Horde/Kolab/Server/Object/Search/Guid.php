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
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Object_Search_Guid
extends Horde_Kolab_Server_Object_Search_Base
{
    /**
     * Perform the search.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The search criteria.
     *
     * @return mixed The search result.
     */
    public function search()
    {
        $criteria   = func_get_arg(0);

        $params = array(
            'attributes' => Horde_Kolab_Server_Object_Top::ATTRIBUTE_GUID
        );
        $data = $this->_composite->server->find($criteria, $params);
        return self::guidFromResult($data);
    }
}