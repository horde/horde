<?php
/**
 * Return only a single search result.
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
 * Return only a single search result.
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
class Horde_Kolab_Server_Search_Operation_Constraint_Single
implements Horde_Kolab_Server_Search_Operation_Interface
{
    /**
     * A link to the search.
     *
     * @var Horde_Kolab_Server_Search_Operation
     */
    private $_search;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Server_Search $search The search being restricted.
     */
    public function __construct(
        Horde_Kolab_Server_Search_Operation_Interface $search
    ) {
        $this->_search = $search;
    }
    
    /**
     * Return the reference to the server structure.
     *
     * @return Horde_Kolab_Server_Structure_Interface
     */
    public function getStructure()
    {
        return $this->_search->getStructure();
    }

    /**
     * Delegate to the actual search operation.
     *
     * @param string $method The name of the called method.
     * @param array  $args   Arguments of the call.
     *
     * @return array The search result.
     */
    public function __call($method, $args)
    {
        $args = func_get_args();
        $result = call_user_func_array(array($this->_search, $method), $args);
        return array_shift($result);
    }
}