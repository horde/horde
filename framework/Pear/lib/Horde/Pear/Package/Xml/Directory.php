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
     * The directory node.
     *
     * @var Horde_Pear_Package_Xml_Element_Directory
     */
    private $_element;

    /**
     * The list of subdirectories.
     *
     * @var array
     */
    private $_subdirectories = array();

    /**
     * The list of files in this directory.
     *
     * @var array
     */
    private $_files;

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Xml_Element_Directory $dir The directory element.
     */
    public function __construct(Horde_Pear_Package_Xml_Element_Directory $dir)
    {
        $this->_element = $dir;
        $subdirectories = $this->_element->getSubdirectories();
        foreach ($subdirectories as $name => $element) {
            $this->_subdirectories[$name] = new Horde_Pear_Package_Xml_Directory(
                $element
            );
        }
        $this->_files = $this->_element->getFiles();
    }

    /**
     * Return the directory node.
     *
     * @return DOMNode The directory node.
     */
    public function getDirectory()
    {
        return $this->_element;
    }

    /**
     * Return the list of files in this hierarchy.
     *
     * @return array The file list.
     */
    public function getFiles()
    {
        $result = array();
        foreach ($this->_subdirectories as $directory) {
            $result = array_merge(
                $result,
                array_map(
                    array($this, '_prependDirectory'),
                    $directory->getFiles()
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
     * Prepend the directory name of this directory to the path name.
     *
     * @param string $path The input path name.
     *
     * @return The completed path.
     */
    private function _prependDirectory($path)
    {
        return strtr(
            $this->_element->getName() . '/' . $path, array('//' => '/')
        );
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
        $this->_files[basename($file)] = $this->_element->insertFile(
            basename($file),
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
        $this->_files[basename($file)]->remove($this->_element);
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
            $this->_subdirectories[$next] = new Horde_Pear_Package_Xml_Directory(
                $this->_element->insertSubDirectory($next),
                $this->_element->getLevel() + 1
            );
        }
        return $this->_subdirectories[$next]->getParent($tree);
    }
}