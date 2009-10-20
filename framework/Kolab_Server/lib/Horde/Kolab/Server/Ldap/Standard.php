<?php
/**
 * The driver for accessing objects stored in standard LDAP.
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
 * This class provides methods to deal with objects stored in
 * a standard LDAP db.
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
class Horde_Kolab_Server_Ldap_Standard extends Horde_Kolab_Server_Ldap
{
    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param Horde_Kolab_Server_Query_Element $criteria The criteria for the search.
     * @param string                           $parent   The parent to search below.
     * @param array                            $params   Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow(
        Horde_Kolab_Server_Query_Element $criteria,
        $parent,
        array $params = array()
    ) {
        $query = new Horde_Kolab_Server_Query_Ldap($criteria);
        return $this->_search((string) $query, $params, $parent);
    }
}
