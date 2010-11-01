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
 * Defines the interface of the search handler for a Kolab Server.
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
interface Horde_Kolab_Server_Search_Interface
{
    /**
     * Set the composite server reference for this object.
     *
     * @param Horde_Kolab_Server_Composite $composite A link to the composite
     *                                                server handler.
     */
    public function setComposite(
        Horde_Kolab_Server_Composite $composite
    );

    /**
     * Returns the set of search operations supported by this server type.
     *
     * @return array An array of supported search operations.
     */
    public function getSearchOperations();

    /**
     * Capture undefined calls.
     *
     * @param string $method The name of the called method.
     * @param array  $args   Arguments of the call.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception
     */
    public function __call($method, $args);
}