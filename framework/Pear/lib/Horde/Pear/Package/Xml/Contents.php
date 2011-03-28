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
     * The package.xml handler to operate on.
     *
     * @var Horde_Pear_Package_Xml
     */
    private $_xml;

    /**
     * The root of the file list section.
     *
     * @var DOMNode
     */
    private $_filelist;

    /**
     * The list of directories in the contents section.
     *
     * @var array
     */
    private $_dir_list = array();

    /**
     * The list of files in the "contents" section.
     *
     * @var array
     */
    private $_file_list = array();

    /**
     * The list of files in the "filelist" section.
     *
     * @var array
     */
    private $_install_list = array();

    /**
     * Constructor.
     *
     * @param Horde_Pear_Package_Xml $xml      The package.xml handler
     *                                         to operate on.
     * @param DOMNode                $contents The root node for the
     *                                         "contents" listing.
     * @param DOMNode                $filelist The root node for the
     *                                         "filelist" listing.
     */
    public function __construct(
        Horde_Pear_Package_Xml $xml,
        DOMNode $contents,
        DOMNode $filelist
    ) {
        $this->_xml = $xml;
        $this->_filelist = $filelist;
        $this->_populateContents('', $contents, 1);
        $this->_populateFileList();
    }

    /**
     * Populate the existing content list from the XML.
     *
     * @param string  $path   The path of the current directory.
     * @param DOMNode $dir    The node of the current directory.
     * @param int     $level  Current depth of the tree.
     *
     * @return NULL
     */
    private function _populateContents($path, $dir, $level)
    {
        if (empty($path)) {
            $key = '/';
        } else {
            $key = $path;
        }
        $this->_dir_list[$key] = array($dir, $level, $dir->lastChild);
        foreach ($this->_xml->findNodesRelativeTo('./p:file', $dir) as $file) {
            $this->_file_list[$path . '/' . $file->getAttribute('name')] = array($dir, $file);
        }
        foreach ($this->_xml->findNodesRelativeTo('./p:dir', $dir) as $directory) {
            $this->_populateContents(
                $path . '/' . $directory->getAttribute('name'),
                $directory,
                $level + 1
            );
        }
    }

    /**
     * Populate the existing file list from the XML.
     *
     * @param DOMNode $filelist The root node of the file list.
     *
     * @return NULL
     */
    private function _populateFileList()
    {
        foreach ($this->_xml->findNodesRelativeTo('./p:install', $this->_filelist) as $file) {
            $this->_install_list['/' . $file->getAttribute('name')] = $file;
        }
    }

    /**
     * Update the file list.
     *
     * @param array $files The new file list.
     *
     * @return NULL
     */
    public function update(Horde_Pear_Package_Contents $contents)
    {
        $files = $contents->getContents();
        $removed = array_diff(array_keys($this->_file_list), array_keys($files));
        foreach (array_keys($files) as $file) {
            $this->add($file, $files[$file]);
        }
        foreach ($removed as $file) {
            $this->delete($file);
        }
    }

    /**
     * Add a file to the list.
     *
     * @param string $file   The file name.
     * @param array  $params Additional file parameters.
     *
     * @return NULL
     */
    public function add($file, $params)
    {
        if (!in_array($file, array_keys($this->_file_list))) {
            list($parent, $level, $bottom) = $this->ensureParent($file);
            $this->_file_list[$file] = array(
                $parent,
                $this->_xml->appendFile(
                    $parent, $bottom, basename($file), $level + 1, $params['role']
                )
            );
        }
        if (!in_array($file, array_keys($this->_install_list))) {
            $this->_install_list[$file] = $this->_xml->appendInstall(
                $this->_filelist, substr($file, 1), $params['as']
            );
        }
    }

    /**
     * Deöete a file frp, the list.
     *
     * @param string $file The file name.
     *
     * @return NULL
     */
    public function delete($file)
    {
        $ws = trim($this->_file_list[$file][1]->nextSibling->textContent);
        if (empty($ws)) {
            $this->_file_list[$file][0]->removeChild(
                $this->_file_list[$file][1]->nextSibling
            );
        }
        $this->_file_list[$file][0]->removeChild($this->_file_list[$file][1]);

        if (isset($this->_install_list[$file])) {
            $ws = trim($this->_install_list[$file]->nextSibling->textContent);
            if (empty($ws)) {
                $this->_filelist->removeChild(
                    $this->_install_list[$file]->nextSibling
                );
            }
            $this->_filelist->removeChild($this->_install_list[$file]);
        }
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