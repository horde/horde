<?php
/**
 * Ensures that a search yields only a single return value.
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
 * Ensures that a search yields only a single return value.
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
class Horde_Kolab_Server_Object_Search_Constraint_Strict
implements Horde_Kolab_Server_Object_Search
{
    /**
     * A link to the search.
     *
     * @var Horde_Kolab_Server_Search
     */
    private $_search;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Search $search The search being restricted.
     */
    public function __construct(Horde_Kolab_Server_Search $search)
    {
        $this->_search = $search;
    }
    
    /**
     * Perform the search.
     *
     * @return mixed The search result.
     */
    public function search()
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this->_search, 'search'), $args);
        if (count($result) > 1) {
            throw new Horde_Kolab_Server_Exception(
                sprintf(
                    "Found %s results when expecting only one!",
                    count($result)
                ),
                Horde_Kolab_Server_Exception::SEARCH_CONSTRAINT_TOO_MANY
            );
        }
        return $result;
    }
}