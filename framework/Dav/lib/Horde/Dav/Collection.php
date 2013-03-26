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

use \Sabre\DAV;

/**
 * A collection (directory) object.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Dav
 */
class Horde_Dav_Collection extends Sabre\DAV\Collection
{
    /**
     * A registry object.
     *
     * @var Horde_Registry
     */
    protected $_registry;

    /**
     * The path to the current collection.
     *
     * @var string
     */
    protected $_path;

    /**
     * Collection details.
     *
     * @var array
     */
    protected $_item;

    /**
     * Constructor.
     *
     * @param Horde_Registry $registry  A registry object.
     * @param string $path              The path to this collection.
     * @param array $item               Collection details.
     */
    public function __construct(Horde_Registry $registry, $path = null,
                                array $item = array())
    {
        $this->_registry = $registry;
        $this->_path = $path;
        $this->_item = $item;
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
        if (!$this->_path) {
            return 'root';
        }
        list($dir, $base) = DAV\URLUtil::splitPath($this->_path);
        return $base;
    }

    /**
     * Returns the last modification time, as a unix timestamp
     *
     * @return int
     */
    public function getLastModified()
    {
        if (!empty($this->_item['modified'])) {
            return $this->_item['modified'];
        }
        if (!empty($this->_item['created'])) {
            return $this->_item['created'];
        }
        return parent::getLastModified();
    }

    /**
     * Returns an array with all the child nodes
     *
     * @return DAV\INode[]
     */
    public function getChildren()
    {
        if (!$this->_path) {
            $apps = array();
            foreach ($this->_registry->listApps() as $app) {
                if ($this->_registry->hasMethod('browse', $app)) {
                    $apps[] = new Horde_Dav_Collection($this->_registry, $app);
                }
            }
            return $apps;
        }

        list($base) = explode('/', $this->_path);
        try {
            $items = $this->_registry->callByPackage($base, 'browse', array('path' => $this->_path, 'properties' => array('name', 'browseable', 'contenttype', 'contentlength', 'created', 'modified')));
        } catch (Horde_Exception $e) {
            throw new DAV\Exception($e);
        }

        if ($items === false) {
            throw new DAV\Exception\NotFound($this->_path . ' not found');
        }

        if (empty($items)) {
            // No content exists at this level.
            return array();
        }

        /* A directory full of objects has been returned. */
        $list = array();
        foreach ($items as $path => $item) {
            if ($item['browseable']) {
                $list[] = new Horde_Dav_Collection($this->_registry, $path, $item);
            } else {
                $list[] = new Horde_Dav_File($this->_registry, $path, $item);
            }
        }
        return $list;
    }
}
