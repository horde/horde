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
     * @param string                                   $name   The name of
     *                                                         the directory.
     * @param Horde_Pear_Package_Xml_Element_Directory $parent The parent
     *                                                         directory.
     */
    public function __construct($name, $parent = null)
    {
        $this->_name = $name;
        if ($parent instanceOf Horde_Pear_Package_Xml_Element_Directory) {
            $this->_xml = $parent->getDocument();
            $this->_path = $parent->getPath() . '/' . $name;
            $this->_level = $parent->getLevel() + 1;
        } else {
            $this->_path = '';
            $this->_level = 1;
        }
    }

    public function setDocument(Horde_Pear_Package_Xml $xml)
    {
        $this->_xml = $xml;
    }

    public function getDocument()
    {
        if ($this->_xml === null) {
            throw new Horde_Pear_Exception('The XML document has been left undefined!');
        }
        return $this->_xml;
    }

    public function setDirectoryNode(DOMNode $directory)
    {
        $this->_dir = $directory;
    }

    public function getDirectoryNode()
    {
        if ($this->_dir === null) {
            throw new Horde_Pear_Exception('The directory node has been left undefined!');
        }
        return $this->_dir;
    }

    /**
     * Return the level of depth in the tree for this directory.
     *
     * @return int The level.
     */
    public function getLevel()
    {
        return $this->_level;
    }

    /**
     * Return the full path to this element.
     *
     * @return string The path.
     */
    public function getPath()
    {
        return $this->_path;
    }

    public function insert($point, $parent)
    {
        $this->_xml->_insertWhiteSpaceBefore($point, "\n " . str_repeat(' ', $this->_level));
        $dir = $this->_xml->create('dir', array('name' => $this->_name));
        $parent->insertBefore($dir, $point);
        $this->_xml->_insertWhiteSpaceBefore($point, " ");
        $this->_xml->_insertCommentBefore($point, ' ' . $this->_path . ' ');
        $this->_xml->_insertWhiteSpace($dir, "\n" . str_repeat(' ', $this->_level + 1));
        $this->setDirectoryNode($dir);
    }

    public function getSubdirectories()
    {
        $result = array();
        foreach ($this->_xml->findNodesRelativeTo('./p:dir', $this->getDirectoryNode()) as $directory) {
            $name = $directory->getAttribute('name');
            $result[$name] = new Horde_Pear_Package_Xml_Element_Directory(
                $name,
                $this
            );
            $result[$name]->setDirectoryNode($directory);
        }
        return $result;
    }

    public function getFiles()
    {
        $result = array();
        foreach ($this->_xml->findNodesRelativeTo('./p:file', $this->getDirectoryNode()) as $file) {
            $result[$file->getAttribute('name')] = $file;
        }
        return $result;
    }
}