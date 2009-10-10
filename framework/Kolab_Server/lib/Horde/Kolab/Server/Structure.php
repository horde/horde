<?php
/**
 * A simple structural handler for a tree of objects.
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
 * The interface definition for the handlers dealing with the Kolab Server
 * object tree structure.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
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
interface Horde_Kolab_Server_Structure
{
    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects();

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid);

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info);
}
