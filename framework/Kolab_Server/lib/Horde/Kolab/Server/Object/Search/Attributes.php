<?php
/**
 * Basic attributes search.
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
 * Basic attributes search.
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
class Horde_Kolab_Server_Object_Search_Attributes
extends Horde_Kolab_Server_Object_Search_Base
{
    /**
     * Perform the search.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria   The search criteria.
     * @param array                            $attributes The attributes to
     *                                                     retrieve.
     *
     * @return mixed The search result.
     */
    public function search()
    {
        $criteria   = func_get_arg(0);
        $attributes = func_get_arg(1);

        $params = array('attributes' => $attributes);
        return $this->_composite->server->find($criteria, $params);
    }
}