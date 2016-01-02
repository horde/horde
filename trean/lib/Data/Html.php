<?php
/**
 * Horde_Data implementation for Mozilla's HTML format.
 *
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Trean
 */
class Trean_Data_Html extends Horde_Data_Base
{
    protected $_extension = 'html';
    protected $_contentType = 'text/html';
    protected $_folders = array();

    public function importData($contents, $header = false)
    {
        $data = array();
        $lines = file($contents);
        $rows = array();
        foreach ($lines as $line) {
            if (strpos($line, '<DT><H3') !== false) {
                // Start of a folder.
                $this->_folders[] = trim(strip_tags($line));
            } elseif (strpos($line, '</DL>') !== false) {
                array_pop($this->_folders);
                // End of folder.
            } elseif (preg_match("/<DT><A HREF=\"*(.*?)\" ADD_DATE=\"*(.*?)\".*>(.*)<\/A>/", $line, $temp)) {
                // Bookmark.
                $rows[] = array(
                    'bookmark_url' => trim($temp[1]),
                    'bookmark_title' => (count($temp) > 3) ? trim($temp[3]) : trim($temp[2]),
                    'bookmark_description' => '',
                    'bookmark_tags' => $this->_folders,
                    'bookmark_dt' => (count($temp) > 3) ? new Horde_Date($temp[2]) : false
                );
            } elseif (strpos($line, '<DD>') !== false) {
                    // Should be description of previous bookmark.
                    $rows[count($rows) - 1]['bookmark_description'] = trim(strip_tags($line));
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
            return $this->importData($_FILES['import_file']['tmp_name']);
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
