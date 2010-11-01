<?php
/**
 * Handles search operations provided by the objects registered to the
 * server structure.
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
 * Handles search operations provided by the objects registered to the
 * server structure.
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
class Horde_Kolab_Server_Search_Base
implements Horde_Kolab_Server_Search_Interface
{
    /**
     * A link to the composite server handler.
     *
     * @var Horde_Kolab_Server_Composite
     */
    private $_composite;

    /**
     * The search methods offered by the object defined for this server.
     *
     * @var array
     */
    private $_searches;

    /**
     * Set the composite server reference for this object.
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     *
     * @return NULL
     */
    public function setComposite(
        Horde_Kolab_Server_Composite $composite
    ) {
        $this->_composite = $composite;
        $this->_searches = $this->_getSearchOperations();
    }

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    private function _getSearchOperations()
    {
        $server_searches = array();
        foreach ($this->_composite->structure->getSearchOperations() as $search_class) {
            if (!class_exists($search_class)) {
                throw new Horde_Kolab_Server_Exception(
                    sprintf(
                        "%s::getSearchOperations specified non-existing class \"%s\"!",
                        get_class($this->_composite->structure),
                        $search_class
                    )
                );
            }
            $methods = get_class_methods($search_class);
            unset($methods['getComposite']);
            unset($methods['__construct']);
            foreach ($methods as $method) {
                $server_searches[$method] = array('class' => $search_class);
            }
        }
        return $server_searches;
    }

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        return $this->_searches;
    }

    /**
     * Capture undefined calls and assume they refer to a search operation.
     *
     * @param string $method The name of the called method.
     * @param array  $args   Arguments of the call.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function __call($method, $args)
    {
        if (in_array($method, array_keys($this->_searches))) {
            $class = $this->_searches[$method]['class'];
            $search = new $class($this->_composite->structure);
            return call_user_func_array(array($search, $method), $args);
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf(
                "The server type \"%s\" with structure \"%s\" does not support"
                . " method \"%s\"!",
                get_class($this->_composite->server),
                get_class($this->_composite->structure),
                $method
            )
        );
    }

}