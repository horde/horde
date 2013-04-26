<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */

use Sabre\DAV;

/**
 * A collection (directory) object for the root folder.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_RootCollection extends DAV\Collection
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * Additional collections.
     *
     * @var array
     */
    protected $_collections = array();

    /**
     * The path to a MIME magic database.
     *
     * @var string
     */
    protected $_mimedb;

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry  A registry object.
     * @param array $collections        Additional collections to add to the
     *                                  root node.
     * @param string $mimedb            Location of a MIME magic database.
     */
    public function __construct(Horde_Registry $registry,
                                array $collections,
                                $mimedb)
    {
        $this->_registry = $registry;
        $this->_collections = $collections;
        $this->_mimedb = $mimedb;
    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName()
    {
        return 'root';
    }

    /**
     * Returns an array with all the child nodes
     *
     * @return DAV\INode[]
     */
    public function getChildren()
    {
        $apps = $this->_collections;
        foreach ($this->_registry->listApps() as $app) {
            if ($this->_registry->hasMethod('browse', $app)) {
                $apps[] = new Horde_Dav_Collection(
                    $app,
                    array(),
                    $this->_registry,
                    $this->_mimedb
                );
            }
        }
        return $apps;
    }
}
