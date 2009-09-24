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
     * @param array              $params  Parameter array.
     */
    public function __construct($params = array())
    {
        $this->params = $params;
    }

    /**
     * Set the server reference for this object.
     *
     * @param Horde_Kolab_Server &$server A link to the server handler.
     */
    public function setServer($server)
    {
        $this->server = $server;
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

    /**
     * Quote an UID part.
     *
     * @param string $id   The UID part.
     *
     * @return string The quoted part.
     */
    abstract public function quoteForUid($id);

    /**
     * Quote an filter part.
     *
     * @param string $part   The filter part.
     *
     * @return string The quoted part.
     */
    abstract public function quoteForFilter($part);
}
