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
 * An abstract class definiing methods to deal with an object tree structure.
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
abstract class Horde_Kolab_Server_Structure
{
    /**
     * A link to the server handler.
     *
     * @var Horde_Kolab_Server
     */
    protected $server;

    /**
     * Structure parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * Construct a new Horde_Kolab_Server_Structure object.
     *
     * @param Horde_Kolab_Server &$server A link to the server handler.
     * @param array              $params  Parameter array.
     */
    public function __construct(&$server, $params = array())
    {
        $this->server = &$server;
        $this->params = $params;
    }

    /**
     * Attempts to return a concrete Horde_Kolab_Server_Structure instance based
     * on $driver.
     *
     * @param mixed              $driver  The type of concrete Horde_Kolab_Server
     *                                    subclass to return.
     * @param Horde_Kolab_Server &$server A link to the server handler .
     * @param array              $params  A hash containing any additional
     *                                    configuration or connection
     *                                    parameters a subclass might need.
     *
     * @return Horde_Kolab_Server_Structure The newly created concrete
     *                            Horde_Kolab_Server_Structure instance.
     *
     * @throws Horde_Kolab_Server_Exception If the requested Horde_Kolab_Server_Structure
     *                                      subclass could not be found.
     */
    static public function &factory($driver, &$server, $params = array())
    {
        if (class_exists($driver)) {
            $class = $driver;
        } else {
            $class = 'Horde_Kolab_Server_Structure_' . ucfirst(basename($driver));
            if (!class_exists($class)) {
                throw new Horde_Kolab_Server_Exception(
                    'Structure type definition "' . $class . '" missing.');
            }
        }
        $structure = new $class($server, $params);
        return $structure;
    }

    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    abstract public function getSupportedObjects();

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
    abstract public function determineType($uid);

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
    abstract public function generateServerUid($type, $id, $info);
}
