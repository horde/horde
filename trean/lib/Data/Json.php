<?php
/**
 * Horde_Data implementation for JSON import.
 *
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */
class Trean_Data_Json extends Horde_Data_Base
{
    protected $_extension = 'json';
    protected $_contentType = 'text/json';
    protected $_tagMap = array();
    protected $_parentMap = array();

    public function importData($contents, $header = false)
    {
        $data = array();
        $json = Horde_Serialize::unserialize($contents, Horde_Serialize::JSON);
        return $this->_parseJson($json->children, null);
    }

    protected function _parseFolders($data)
    {
        // Need a first pass to grab all the folders
        foreach ($data as $child) {
            if ($child->type == 'text/x-moz-place-container') {
                if (empty($child->root)) {
                    $this->_tagMap[$child->id] = $child->title;
                    $this->_parentMap[$child->id] = $child->parent;
                }
                if (!empty($child->children)) {
                    $this->_parseFolders($child->children);
                }
            }
        }
    }

    protected function _parseJson($data, $container)
    {
        // Need a first pass to grab all the folders
        $this->_parseFolders($data);
        return $this->_parseBookmarks($data);
    }

    protected function _parseBookmarks($data, $container = null)
    {
        $rows  = array();
        foreach ($data as $child) {
            if ($child->type == 'text/x-moz-place-container') {
                $rows = array_merge($this->_parseBookmarks($child->children, $child), $rows);
            }
            if ($child->type == 'text/x-moz-place') {
                $desc = '';
                if (!empty($child->annos)) {
                    foreach ($child->annos as $property) {
                        switch ($property->name) {
                        case 'Places/SmartBookmark':
                            // Ignore "SmartBookmarks"
                            continue 3;
                        case 'bookmarkProperties/description':
                            $desc = $property->value;
                            break 2;
                        }
                    }
                }
                $tags = !empty($child->tags) ? explode(',', $child->tags) : array();
                $current_parent = $container->parent;
                while (!empty($current_parent)) {
                    if (!empty($this->_tagMap[$current_parent])) {
                        $tags[] = $this->_tagMap[$current_parent];
                    }
                    $current_parent = !empty($this->_parentMap[$current_parent])
                        ? $this->_parentMap[$current_parent]
                        : false;
                }
                if (!empty($container) && empty($container->root)) {
                    $tags[] = $container->title;
                }

                $rows[] = array(
                    'bookmark_url' => $child->uri,
                    'bookmark_title' => $child->title,
                    'bookmark_description' => $desc,
                    'bookmark_tags' => $tags,
                    'bookmark_dt' => !empty($child->dateAdded) ? new Horde_Date(substr($child->dateAdded, 0, 10)) : false
                );
            }
        }

        return $rows;
    }

    /**
     * Takes all necessary actions for the given import step, parameters and
     * form values and returns the next necessary step.
     *
     * @param integer $action  The current step. One of the IMPORT_* constants.
     * @param array $param     An associative array containing needed
     *                         parameters for the current step.
     *
     * @return mixed  Either the next step as an integer constant or imported
     *                data set after the final step.
     * @throws Horde_Data_Exception
     */
    public function nextStep($action, $param = array())
    {
        switch ($action) {
        case Horde_Data::IMPORT_FILE:
            parent::nextStep($action, $param);
            return $this->importFile($_FILES['import_file']['tmp_name']);
        }
    }

    /**
     * Stub to return exported data.
     */
    public function exportData($data, $method = 'REQUEST')
    {
        // TODO
    }

    /**
     * Stub to export data to a file.
     */
    public function exportFile($filename, $data)
    {
        // TODO
    }

}
