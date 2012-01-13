<?php
/**
 * Handles a directory in the contents list.
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
 * Handles a directory in the contents list.
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
class Horde_Pear_Package_Xml_Directory
{
    /**
     * The directory node.
     *
     * @var Horde_Pear_Package_Xml_Element_Directory
     */
    private $_element;

    /**
     * The parent directory.
     *
     * @var Horde_Pear_Package_Xml_Directory
     */
    private $_parent;

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
     * @param Horde_Pear_Package_Xml_Element_Directory $dir    The directory element.
     * @param mixed                                    $parent The parent directory
     *                                                         or the XML document.
     */
    public function __construct(Horde_Pear_Package_Xml_Element_Directory $dir,
                                $parent)
    {
        $this->_element = $dir;
        $this->_parent = $parent;
        $subdirectories = $this->_element->getSubdirectories();
        foreach ($subdirectories as $name => $element) {
            $this->_subdirectories[$name] = $this->_create($element, $this);
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
     * Create a new directory handler.
     *
     * @param Horde_Pear_Package_Xml_Element_Directory $element The represented element.
     * @param Horde_Pear_Package_Xml_Directory         $parent  The parent directory.
     *
     * @return Horde_Pear_Package_Xml_Directory
     */
    private function _getRoot()
    {
        if ($this->_parent instanceOf Horde_Pear_Package_Xml_Directory) {
            return $this->_parent->_getRoot();
        } else {
            return $this->_parent;
        }
    }

    /**
     * Create a new directory handler.
     *
     * @param Horde_Pear_Package_Xml_Element_Directory $element The represented element.
     * @param Horde_Pear_Package_Xml_Directory         $parent  The parent directory.
     *
     * @return Horde_Pear_Package_Xml_Directory
     */
    private function _create(Horde_Pear_Package_Xml_Element_Directory $element,
                             Horde_Pear_Package_Xml_Directory $parent)
    {
        return $this->_getRoot()->createDirectory($element, $parent);
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
            $params['role'],
            $this->_getFileInsertionPoint(basename($file))
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
        $this->_files[basename($file)]->delete();
        unset($this->_files[basename($file)]);
        $this->_prune();
    }

    /**
     * Delete a subdirectory from the list.
     *
     * @param string $dir The directory name.
     *
     * @return NULL
     */
    private function _deleteSubdirectory($dir)
    {
        unset($this->_subdirectories[$dir]);
        $this->_prune();
    }

    /**
     * Prune this directory if it is empty.
     *
     * @return NULL
     */
    private function _prune()
    {
        if (empty($this->_files) && empty($this->_subdirectories)) {
            $this->_element->delete();
            if ($this->_parent instanceOf Horde_Pear_Package_Xml_Directory) {
                $this->_parent->_deleteSubdirectory($this->_element->getName());
            }
        }
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
        if (empty($tree) && !strlen($next)) {
            return $this;
        }
        if (!isset($this->_subdirectories[$next])) {
            $this->_subdirectories[$next] = $this->_create(
                $this->_element->insertSubDirectory(
                    $next,
                    $this->_getDirectoryInsertionPoint($next)
                ),
                $this
            );
        }
        return $this->_subdirectories[$next]->getParent($tree);
    }

    /**
     * Identify the insertion point for a new directory.
     *
     * @param string $new The key for the new element.
     *
     * @return mixed The insertion point.
     */
    private function _getDirectoryInsertionPoint($new)
    {
        $keys = array_keys($this->_subdirectories);
        array_push($keys, $new);
        usort($keys, array($this, '_fileOrder'));
        $pos = array_search($new, $keys);
        if ($pos < count($this->_subdirectories)) {
            return $this->_subdirectories[$keys[$pos + 1]]->getDirectory()->getDirectoryNode();
        } else {
            if (empty($this->_files)) {
                return null;
            } else {
                $keys = array_keys($this->_files);
                usort($keys, array($this, '_fileOrder'));
                return $this->_files[$keys[0]]->getFileNode();
            }
        }
    }

    /**
     * Sort order for files in the content list.
     *
     * @param string $a First path.
     * @param string $b Second path.
     *
     * @return int Sort comparison result.
     */
    private function _fileOrder($a, $b)
    {
        return strnatcasecmp($a, $b);
    }

    /**
     * Identify the insertion point for a new file.
     *
     * @param string $new The key for the new element.
     *
     * @return mixed The insertion point.
     */
    private function _getFileInsertionPoint($new)
    {
        $keys = array_keys($this->_files);
        array_push($keys, $new);
        usort($keys, array($this, '_fileOrder'));
        $pos = array_search($new, $keys);
        if ($pos < count($this->_files)) {
            return $this->_files[$keys[$pos + 1]]->getFileNode();
        } else {
            return null;
        }
    }
}