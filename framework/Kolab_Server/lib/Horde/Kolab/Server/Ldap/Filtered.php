<?php
/**
 * The driver for accessing objects stored in a filtered LDAP.
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
 * a filtered LDAP db.
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
class Horde_Kolab_Server_Ldap_Filtered extends Horde_Kolab_Server_Ldap
{
    /**
     * A global filter to add to each query.
     *
     * @var string
     */
    private $_filter;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Server_Connection $connection The LDAP connection.
     * @param string                        $base_dn    The LDAP server base DN.
     * @param string                        $filter     A global filter to add
     *                                                  to all queries.
     */
    public function __construct(
        Horde_Kolab_Server_Connection_Interface $connection,
        $base_dn,
        $filter = null
    ) {
        parent::__construct($connection, $base_dn);
        $this->_filter  = $filter;
    }

    /**
     * Finds all object data below a parent matching a given set of criteria.
     *
     * @param string $query  The LDAP search query
     * @param string $parent The parent to search below.
     * @param array  $params Additional search parameters.
     *
     * @return Horde_Kolab_Server_Result The result object.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function findBelow($query, $parent, array $params = array())
    {
        $query = '(&(' . $this->_filter . ')' . $query . ')';
        return $this->_search($query, $params, $parent);
    }
}
