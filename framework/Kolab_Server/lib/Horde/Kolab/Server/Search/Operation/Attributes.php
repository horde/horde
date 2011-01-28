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
class Horde_Kolab_Server_Search_Operation_Attributes
extends Horde_Kolab_Server_Search_Operation_Base
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
    public function searchAttributes(
        Horde_Kolab_Server_Query_Element $criteria,
        array $attributes
    ) {
        $params = array('attributes' => $attributes);
        return $this->getComposite()->structure->find($criteria, $params);
    }
}