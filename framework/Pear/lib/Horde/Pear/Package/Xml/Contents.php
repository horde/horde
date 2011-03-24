<?php
/**
 * Handles the XML contents list.
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
 * Handles the XML contents list.
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
class Horde_Pear_Package_Xml_Contents
{
    /**
     * The root node for the content listing.
     *
     * @var DOMNode
     */
    private $_root;

    /**
     * The package.xml handler to operate on.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_xml;

    /**
     * The list of directories in the contents section.
     *
     * @var array
     */
    private $_dir_list = array();

    /**
     * The list of files in the contents section.
     *
     * @var array
     */
    private $_file_list = array();

    /**
     * Constructor.
     *
     * @param DOMNode                $root   The root node for the content listing.
     * @param DOMNode                $bottom The bottom white space node for the
     *                                       content listing.
     * @param Horde_Pear_Package_Xml $xml    The package.xml handler to operate on.
     */
    public function __construct(DOMNode $root, DOMNode $bottom, Horde_Pear_Package_Xml $xml)
    {
        $this->_root = $root;
        $this->_dir_list['/'] = array($root, 1, $bottom);
        $this->_xml = $xml;
    }

    /**
     * Add a file to the list.
     *
     * @param string $file The file name.
     *
     * @return NULL
     */
    public function add($file)
    {
        list($parent, $level, $bottom) = $this->ensureParent($file);
        $this->_xml->appendFile($parent, $bottom, basename($file), $level + 1);
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
        $parent = dirname($current);
        if (!isset($this->_dir_list[$parent])) {
            list($node, $level, $bottom) = $this->ensureParent($parent);
            list($dir, $bottom) = $this->_xml->appendDirectory(
                $node, $bottom, basename($parent), $parent, $level + 1
            );
            $this->_dir_list[$parent] = array($dir, $level + 1, $bottom);
        }
        return $this->_dir_list[$parent];
    }
}