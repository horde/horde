<?php
/**
 * Handles a directory in the contents list.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Handles a directory in the contents list.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml_Directory
{
    /**
     * The package.xml handler to operate on.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_xml;

    /**
     * The directory node.
     *
     * @var DOMNode
     */
    private $_dir;

    /**
     * The path to this directory.
     *
     * @var string
     */
    private $_path;

    /**
     * The level in the tree.
     *
     * @var int
     */
    private $_level;

    /**
     * The list of subdirectories.
     *
     * @var array
     */
    private $_subdirectories;

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Xml $xml   The package.xml handler to
     *                                      operate on.
     * @param DOMNode                $dir   The directory node.
     * @param string                 $path  The path to the current directory.
     * @param int                    $level The level in the tree.
     */
    public function __construct(
        Horde_Pear_Package_Xml $xml,
        DOMNode $dir,
        $path,
        $level
    ) {
        $this->_xml = $xml;
        $this->_dir = $dir;
        $this->_path = $path;
        $this->_level = $level;

        $this->_subdirectories = array();

        foreach ($this->_xml->findNodesRelativeTo('./p:dir', $dir) as $directory) {
            $this->_subdirectories[$directory->getAttribute('name')] = new Horde_Pear_Package_Xml_Directory(
                $xml,
                $directory,
                $path . '/' . $directory->getAttribute('name'),
                $level + 1
            );
        }

    }

    public function getLevel()
    {
        return $this->_level;
    }

    public function getDirectory()
    {
        return $this->_dir;
    }

    /**
     * Ensure the parent directory to the provided element exists.
     *
     * @param string $current The name of the item that needs a parent.
     *
     * @return NULL
     */
    public function ensureParent($current)
    {
        if (is_string($current)) {
            $current = explode('/', dirname($current));
        }
        $next = array_shift($current);
        while ($next === '') {
            $next = array_shift($current);
        }
        if (empty($current) && empty($next)) {
            return array(
                $this->getDirectory(),
                $this->getLevel(),
                $this->getDirectory()->lastChild
            );
        }
        if (!isset($this->_subdirectories[$next])) {
            $directory = $this->_xml->appendDirectory(
                $this->_dir,
                $this->_dir->lastChild,
                $next,
                $this->_path . '/' . $next,
                $this->_level + 1
            );
            $this->_subdirectories[$next] = new Horde_Pear_Package_Xml_Directory(
                $this->_xml,
                $directory,
                $this->_path . '/' . $next,
                $this->_level + 1
            );
        }
        return $this->_subdirectories[$next]->ensureParent($current);
    }
}