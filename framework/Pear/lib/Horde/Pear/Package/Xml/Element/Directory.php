<?php
/**
 * Handles a XML directory node in the contents list.
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
 * Handles a XML directory node in the contents list.
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
class Horde_Pear_Package_Xml_Element_Directory
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
     * The name of this directory.
     *
     * @var string
     */
    private $_name;

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
     * Constructor.
     *
     * @param Horde_Pear_Package_Xml $xml   The package.xml handler to
     *                                      operate on.
     * @param string                 $name  The name of the directory.
     * @param string                 $path  The path to the directory.
     * @param int                    $level The level in the tree.
     */
    public function __construct(
        Horde_Pear_Package_Xml $xml,
        $name,
        $path,
        $level
    ) {
        $this->_xml = $xml;
        $this->_name = $name;
        $this->_path = $path;
        $this->_level = $level;
    }

    public function insert($point, $parent)
    {
        $this->_xml->_insertWhiteSpaceBefore($point, "\n " . str_repeat(' ', $this->_level));
        $dir = $this->_xml->create('dir', array('name' => $this->_name));
        $parent->insertBefore($dir, $point);
        $this->_xml->_insertWhiteSpaceBefore($point, " ");
        $this->_xml->_insertCommentBefore($point, ' ' . $this->_path . ' ');
        $this->_xml->_insertWhiteSpace($dir, "\n" . str_repeat(' ', $this->_level + 1));
        return $dir;
    }
}