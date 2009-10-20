<?php
/**
 * A library for accessing the Kolab user database.
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
 * This class provides methods to deal with Kolab objects stored in
 * the Kolab object db.
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
class Horde_Kolab_Server_Search_Base implements Horde_Kolab_Server_Search
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
     */
    public function setComposite(Horde_Kolab_Server_Composite $composite)
    {
        $this->_composite = $composite;
        $this->_searches = $this->getSearchOperations();
    }

    /*__construct
        /** Initialize the search operations supported by this server. *
        */

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations()
    {
        $server_searches = array();
        foreach ($this->_composite->structure->getSupportedObjects() as $sobj) {
            if (in_array('getSearchOperations', get_class_methods($sobj))) {
                $searches = call_user_func(array($sobj, 'getSearchOperations'));
                foreach ($searches as $search) {
                    $server_searches[$search] = array('class' => $sobj);
                }
            }
        }
        return $server_searches;
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
        if (in_array($method, array_keys($this->searches))) {
            array_unshift($args, $this);
            if (isset($this->searches[$method])) {
                return call_user_func_array(array($this->searches[$method]['class'],
                                                  $method), $args);
            }
        }
        throw new Horde_Kolab_Server_Exception(
            sprintf(
                "The server type \"%s\" with structure \"%s\" does not support method \"%s\"!",
                get_class($this->_composite->server),
                get_class($this->_composite->structure),
                $method
            )
        );
    }

}