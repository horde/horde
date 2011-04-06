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
     * @var Horde_Pear_Package_Xml_Directory
     */
    private $_dir_list;

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
        $element = new Horde_Pear_Package_Xml_Element_Directory('/');
        $element->setDocument($this->_xml);
        $element->setDirectoryNode($contents);
        $this->_dir_list = new Horde_Pear_Package_Xml_Directory(
            $element,
            1
        );
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
        $removed = array_diff($this->_dir_list->getFiles(), array_keys($files));
        foreach ($added as $file) {
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
        $this->_dir_list->addFile($file, $params);
        if (!in_array($file, array_keys($this->_install_list))) {
            $this->_install_list[$file] = $this->_xml->appendInstall(
                $this->_filelist, substr($file, 1), $params['as']
            );
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
            $ws = trim($this->_install_list[$file]->nextSibling->textContent);
            if (empty($ws)) {
                $this->_filelist->removeChild(
                    $this->_install_list[$file]->nextSibling
                );
            }
            $this->_filelist->removeChild($this->_install_list[$file]);
        }
    }
}