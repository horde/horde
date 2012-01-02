<?php
/**
 * Handles the XML contents list.
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
 * Handles the XML contents list.
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
     * @var Horde_Pear_Package_Xml_Directory
     */
    private $_dir_list;

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
    public function __construct(Horde_Pear_Package_Xml $xml, DOMNode $contents,
                                DOMNode $filelist)
    {
        $this->_xml = $xml;
        $this->_filelist = $filelist;
        $element = $this->_xml->createElementDirectory('/');
        $element->setDocument($this->_xml);
        $element->setDirectoryNode($contents);
        $this->_dir_list = $this->_xml->createDirectory($element, $this->_xml);
        $this->_populateFileList();
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
        $added = array_diff(array_keys($files), $this->_dir_list->getFiles());
        $deleted = array_diff($this->_dir_list->getFiles(), array_keys($files));
        foreach ($added as $file) {
            $this->add($file, $files[$file]);
        }
        foreach ($deleted as $file) {
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
        $this->_dir_list->addFile($file, $params);
        if (!in_array($file, array_keys($this->_install_list))) {
            $point = $this->_getInstallInsertionPoint($file);
            if ($point === null) {
                $point = $this->_filelist->lastChild;
            } else {
                if ($point->previousSibling) {
                    $ws = trim($point->previousSibling->textContent);
                    if (empty($ws)) {
                        $point = $point->previousSibling;
                    }
                }
            }
            $this->_install_list[$file] = $this->_xml->insert(
                array(
                    "\n   ",
                    'install' => array(
                        'as' => $params['as'], 'name' => substr($file, 1)
                    ),
                ),
                $point
            );
        }
    }

    /**
     * Identify the insertion point for a new file.
     *
     * @param string $new The key for the new element.
     *
     * @return mixed The insertion point.
     */
    private function _getInstallInsertionPoint($new)
    {
        $keys = array_keys($this->_install_list);
        array_push($keys, $new);
        usort($keys, array($this, '_fileOrder'));
        $pos = array_search($new, $keys);
        if ($pos < (count($keys) - 1)) {
            return $this->_install_list[$keys[$pos + 1]];
        } else {
            return null;
        }
    }

    /**
     * Sort order for files in the installation list.
     *
     * @param string $a First path.
     * @param string $b Second path.
     *
     * @return int Sort comparison result.
     */
    private function _fileOrder($a, $b)
    {
        $ea = explode('/', $a);
        $eb = explode('/', $b);
        while (true) {
            $pa = array_shift($ea);
            $pb = array_shift($eb);
            if ($pa != $pb) {
                if ((count($ea) == 0 || count($eb) == 0)
                    && count($ea) != count($eb)) {
                    return count($ea) < count($eb) ? -1 : 1;
                }
                return strnatcasecmp($pa, $pb);
            }
        }
    }

    /**
     * Delete a file from the list.
     *
     * @param string $file The file name.
     *
     * @return NULL
     */
    public function delete($file)
    {
        $this->_dir_list->deleteFile($file);
        if (isset($this->_install_list[$file])) {
            $this->_xml->removeWhitespace(
                $this->_install_list[$file]->nextSibling
            );
            $this->_filelist->removeChild($this->_install_list[$file]);
        }
    }
}