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