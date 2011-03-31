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
     * The list of files in this directory.
     *
     * @var array
     */
    private $_files;

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

        $this->_files = array();
        foreach ($this->_xml->findNodesRelativeTo('./p:file', $dir) as $file) {
            $this->_files[$file->getAttribute('name')] = $file;
        }
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
     * Return the directory node.
     *
     * @return DOMNode The directory node.
     */
    public function getDirectory()
    {
        return $this->_dir;
    }

    /**
     * Return the name of this directory.
     *
     * @return string The directory name.
     */
    public function getName()
    {
        return $this->_dir->getAttribute('name');
    }

    /**
     * Return the list of files in this hierarchy.
     *
     * @return array The file list.
     */
    public function getFiles()
    {
        return array_map(
            array($this, '_prependRoot'),
            $this->_getFiles()
        );
    }

    /**
     * Return the list of files in this hierarchy.
     *
     * @return array The file list.
     */
    private function _getFiles()
    {
        $result = array();
        foreach ($this->_subdirectories as $directory) {
            $result = array_merge(
                $result,
                array_map(
                    array($this, '_prependDirectory'),
                    $directory->_getFiles()
                )
            );
        }
        $result = array_merge(
            $result,
            array_map(
                array($this, '_prependDirectory'),
                array_keys($this->_files)
            )
        );
        return $result;
    }

    /**
     * Prepend the root directory separator to the path name.
     *
     * @param string $path The input path name.
     *
     * @return The completed path.
     */
    private function _prependRoot($path)
    {
        return '/' . $path;
    }

    /**
     * Prepend the directory name of this directory to the path name.
     *
     * @param string $path The input path name.
     *
     * @return The completed path.
     */
    private function _prependDirectory($path)
    {
        return $this->getName() . '/' . $path;
    }

    /**
     * Add a file to the list.
     *
     * @param string $file   The file name.
     * @param array  $params Additional file parameters.
     *
     * @return NULL
     */
    public function addFile($file, $params)
    {
        $this->getParent(explode('/', dirname($file)))
            ->_addFile($file, $params);
    }

    /**
     * Add a file to the list.
     *
     * @param string $file   The file name.
     * @param array  $params Additional file parameters.
     *
     * @return NULL
     */
    private function _addFile($file, $params)
    {
        $this->_files[basename($file)] = $this->_xml->insertFile(
            $this->_dir,
            $this->_dir->lastChild,
            basename($file),
            $this->_level + 1,
            $params['role']
        );
    }

    /**
     * Delete a file from the list.
     *
     * @param string $file The file name.
     *
     * @return NULL
     */
    public function deleteFile($file)
    {
        $this->getParent(explode('/', dirname($file)))->_deleteFile($file);
    }

    /**
     * Delete a file from the list.
     *
     * @param string $file The file name.
     *
     * @return NULL
     */
    private function _deleteFile($file)
    {
        $this->_xml->removeFile($this->_files[basename($file)], $this->_dir);
    }

    /**
     * Ensure the provided path hierarchy.
     *
     * @param array $tree The path elements that are required.
     *
     * @return DOMNode The parent directory for the file.
     */
    public function getParent($tree)
    {
        $next = array_shift($tree);
        while ($next === '') {
            $next = array_shift($tree);
        }
        if (empty($tree) && empty($next)) {
            return $this;
        }
        if (!isset($this->_subdirectories[$next])) {
            $element = new Horde_Pear_Package_Xml_Element_Directory(
                $this->_xml,
                $next,
                $this->_path . '/' . $next,
                $this->_level + 1
            );
            $directory = $element->insert($this->_dir->lastChild, $this->_dir);
                
            $this->_subdirectories[$next] = new Horde_Pear_Package_Xml_Directory(
                $this->_xml,
                $directory,
                $this->_path . '/' . $next,
                $this->_level + 1
            );
        }
        return $this->_subdirectories[$next]->getParent($tree);
    }
}