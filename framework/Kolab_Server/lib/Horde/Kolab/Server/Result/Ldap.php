<?php
/**
 * Handler for LDAP query results.
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
 * Handler for LDAP query results.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Result_Ldap
implements Horde_Kolab_Server_Result_Interface
{
    /**
     * The search result.
     *
     * @var Horde_Ldap_Search
     */
    private $_search;

    /**
     * Constructor.
     *
     * @param Horde_Ldap_Search $search The LDAP search result.
     */
    public function __construct(Horde_Ldap_Search $search)
    {
        $this->_search = $search;
    }

    /**
     * The number of result entries.
     *
     * @return int The number of elements.
     */
    public function count()
    {
        return $this->_search->count();
    }

    /**
     * Test if the last search exceeded the size limit.
     *
     * @return boolean True if the last search exceeded the size limit.
     */
    public function sizeLimitExceeded()
    {
        return $this->_search->sizeLimitExceeded();
    }

    /**
     * Return the result as an array.
     *
     * @return array The resulting array.
     */
    public function asArray()
    {
        return $this->_search->asArray();
    }
}