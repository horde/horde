<?php
/**
 * Handles a XML file node in the contents list.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Handles a XML file node in the contents list.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Xml_Element_File
{
    /**
     * The package.xml handler to operate on.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_xml;

    /**
     * The parent directory
     *
     * @var Horde_Pear_Package_Xml_Element_Directory
     */
    private $_parent;

    /**
     * The file node.
     *
     * @var DOMNode
     */
    private $_file;

    /**
     * The name of this file.
     *
     * @var string
     */
    private $_name;

    /**
     * The role of this file.
     *
     * @var string
     */
    private $_role;

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
     * @param string                                   $role   The file role.
     */
    public function __construct($name, $parent, $role = null)
    {
        $this->_name = $name;
        $this->_role = $role;
        $this->_parent = $parent;
        $this->_xml = $parent->getDocument();
        $this->_level = $parent->getLevel() + 1;
    }

    /**
     * Set the DOM node of the file entry.
     *
     * @param DOMNode $directory The file node.
     *
     * @return NULL
     */
    public function setFileNode(DOMNode $file)
    {
        $this->_file = $file;
    }

    /**
     * Get the DOM node of the file entry.
     *
     * @return DOMNode The file node.
     */
    public function getFileNode()
    {
        if ($this->_file === null) {
            throw new Horde_Pear_Exception('The file node has been left undefined!');
        }
        return $this->_file;
    }

    /**
     * Insert the file entry into the XML at the given point.
     *
     * @param DOMNode $point Insertion point.
     *
     * @return NULL
     */
    public function insert(DOMNode $point = null)
    {
        if ($point === null) {
            $point = $this->_parent->getDirectoryNode()->lastChild;
        } else {
            if ($point->previousSibling) {
                $ws = trim($point->previousSibling->textContent);
                if (empty($ws)) {
                    $point = $point->previousSibling;
                }
            }
        }

        $this->setFileNode(
            $this->_xml->insert(
                array(
                    "\n " . str_repeat(" ", $this->_level),
                    'file' => array(
                        'name' => $this->_name, 'role' => $this->_role
                    ),
                ),
                $point
            )
        );
    }

    /**
     * Remove the file entry from the XML.
     *
     * @return NULL
     */
    public function delete()
    {
        $file = $this->getFileNode();
        $dir = $this->_parent->getDirectoryNode();
        $this->_xml->removeWhitespace($file->previousSibling);
        $dir->removeChild($file);
    }
}