<?php
/**
 * Basic GUID search.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Basic GUID search.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Search_Operation_Guid
extends Horde_Kolab_Server_Search_Operation_Base
{
    /**
     * Perform the search.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The search criteria.
     *
     * @return array The search result.
     */
    public function searchGuid(
        Horde_Kolab_Server_Query_Element_Interface $criteria
    ) {
        $params = array(
            'attributes' => 'guid'
        );
        $data = $this->getStructure()->find($criteria, $params);
        return self::guidFromResult($data);
    }
}