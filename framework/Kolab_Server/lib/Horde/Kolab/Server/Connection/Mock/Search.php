<?php
/**
 * A mockup class to simulate LDAP search results.
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
 * A mockup class to simulate LDAP search results.
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
class Horde_Kolab_Server_Connection_Mock_Search
extends Horde_Ldap_Search
{
    /**
     * The search result.
     *
     * @var array
     */
    private $_result;

    /**
     * Constructor.
     *
     * @param array $result The search result.
     */
    public function __construct(array $result)
    {
        $this->_result = $result;
    }

    /**
     * The number of result entries.
     *
     * @return int The number of elements.
     */
    public function count()
    {
        return count($this->_result);
    }

    /**
     * Test if the last search exceeded the size limit.
     *
     * @return boolean True if the last search exceeded the size limit.
     */
    public function sizeLimitExceeded()
    {
        return false;
    }

    /**
     * Return the result as an array.
     *
     * @return array The resulting array.
     */
    public function asArray()
    {
        return $this->_result;
    }

}
