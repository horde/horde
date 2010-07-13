<?php
/**
 * @category Horde
 * @package  Data
 */

/**
 * Horde_Data implementation for comma-separated data (CSV).
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data_Csv extends Horde_Data_Base
{
    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = 'csv';

    /**
     * MIME content type.
     *
     * @var string
     */
    protected $_contentType = 'text/comma-separated-values';

    /**
     * Tries to discover the CSV file's parameters.
     *
     * @param string $filename  The name of the file to investigate.
     *
     * @return array  An associative array with the following possible keys:
     * <pre>
     * 'sep':    The field separator
     * 'quote':  The quoting character
     * 'fields': The number of fields (columns)
     * </pre>
     */
    public function discoverFormat($filename)
    {
        return Horde_File_Csv::discoverFormat($filename);
    }

    /**
     * Imports and parses a CSV file.
     *
     * @param string $filename  The name of the file to parse.
     * @param boolean $header   Does the first line contain the field/column
     *                          names?
     * @param string $sep       The field/column separator.
     * @param string $quote     The quoting character.
     * @param integer $fields   The number or fields/columns.
     * @param string $charset   The file's charset.
     * @param string $crlf      The file's linefeed characters.
     *
     * @return array  A two-dimensional array of all imported data rows.  If
     *                $header was true the rows are associative arrays with the
     *                field/column names as the keys.
     * @throws Horde_File_Csv_Exception
     */
    public function importFile($filename, $header = false, $sep = ',',
                               $quote = '', $fields = null,
                               $import_mapping = array(), $charset = null,
                               $crlf = null)
    {
        if (empty($fields)) {
            return array();
        }

        $conf = array(
            'crlf' => $crlf,
            'fields' => $fields,
            'quote' => $quote,
            'sep' => $sep
        );

        /* Strip and keep the first line if it contains the field
         * names. */
        if ($header) {
            $head = Horde_File_Csv::read($filename, $conf);
            if (!empty($charset)) {
                $head = Horde_String::convertCharset($head, $charset, $GLOBALS['registry']->getCharset());
            }
        }

        $data = array();
        while ($line = Horde_File_Csv::read($filename, $conf)) {
            if (!empty($charset)) {
                $line = Horde_String::convertCharset($line, $charset, $GLOBALS['registry']->getCharset());
            }
            if (!isset($head)) {
                $data[] = $line;
            } else {
                $newline = array();
                for ($i = 0; $i < count($head); $i++) {
                    if (isset($import_mapping[$head[$i]])) {
                        $head[$i] = $import_mapping[$head[$i]];
                    }
                    $cell = $line[$i];
                    $cell = preg_replace("/\"\"/", "\"", $cell);
                    $newline[$head[$i]] = empty($cell) ? '' : $cell;
                }
                $data[] = $newline;
            }
        }

        $fp = Horde_File_Csv::getPointer($filename, $conf);
        if ($fp) {
            rewind($fp);
        }

        $this->_warnings = Horde_File_Csv::warning();

        return $data;
    }

    /**
     * Builds a CSV file from a given data structure and returns it as a
     * string.
     *
     * @param array $data      A two-dimensional array containing the data set.
     * @param boolean $header  If true, the rows of $data are associative
     *                         arrays with field names as their keys.
     *
     * @return string  The CSV data.
     */
    public function exportData($data, $header = false,
                               $export_mapping = array())
    {
        if (!is_array($data) || count($data) == 0) {
            return '';
        }

        $export = '';
        $eol = "\n";
        $head = array_keys(current($data));
        if ($header) {
            foreach ($head as $key) {
                if (!empty($key)) {
                    if (isset($export_mapping[$key])) {
                        $key = $export_mapping[$key];
                    }
                    $export .= '"' . $key . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . $eol;
        }

        foreach ($data as $row) {
            foreach ($head as $key) {
                $cell = $row[$key];
                if (!empty($cell) || $cell === 0) {
                    $export .= '"' . $cell . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . $eol;
        }

        return $export;
    }

    /**
     * Builds a CSV file from a given data structure and triggers its
     * download.  It DOES NOT exit the current script but only outputs the
     * correct headers and data.
     *
     * @param string $filename  The name of the file to be downloaded.
     * @param array $data       A two-dimensional array containing the data
     *                          set.
     * @param boolean $header   If true, the rows of $data are associative
     *                          arrays with field names as their keys.
     */
    public function exportFile($filename, $data, $header = false,
                               $export_mapping = array())
    {
        $export = $this->exportData($data, $header, $export_mapping);
        $this->_browser->downloadHeaders($filename, 'application/csv', false, strlen($export));
        echo $export;
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

            /* Move uploaded file so that we can read it again in the next
               step after the user gave some format details. */
            $file_name = Horde_Util::getTempFile('import', false);
            if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_name)) {
                throw new Horde_Data_Exception('The uploaded file could not be saved.');
            }
            $_SESSION['import_data']['file_name'] = $file_name;

            /* Try to discover the file format ourselves. */
            $conf = $this->discoverFormat($file_name);
            if (!$conf) {
                $conf = array('sep' => ',');
            }
            $_SESSION['import_data'] = array_merge($_SESSION['import_data'], $conf);

            /* Check if charset was specified. */
            $_SESSION['import_data']['charset'] = $this->_vars->charset;

            /* Read the file's first two lines to show them to the user. */
            $_SESSION['import_data']['first_lines'] = '';
            $fp = @fopen($file_name, 'r');
            if ($fp) {
                $line_no = 1;
                while ($line_no < 3 && $line = fgets($fp)) {
                    if (!empty($_SESSION['import_data']['charset'])) {
                        $line = Horde_String::convertCharset($line, $_SESSION['import_data']['charset'], $GLOBALS['registry']->getCharset());
                    }
                    $newline = Horde_String::length($line) > 100 ? "\n" : '';
                    $_SESSION['import_data']['first_lines'] .= substr($line, 0, 100) . $newline;
                    $line_no++;
                }
            }
            return Horde_Data::IMPORT_CSV;

        case Horde_Data::IMPORT_CSV:
            $_SESSION['import_data']['header'] = $this->_vars->header;
            $import_mapping = array();
            if (isset($param['import_mapping'])) {
                $import_mapping = $param['import_mapping'];
            }
            $import_data = $this->importFile(
                $_SESSION['import_data']['file_name'],
                $_SESSION['import_data']['header'],
                $this->_vars->sep,
                $this->_vars->quote,
                $this->_vars->fields,
                $import_mapping,
                $_SESSION['import_data']['charset'],
                $_SESSION['import_data']['crlf']
            );
            $_SESSION['import_data']['data'] = $import_data;
            unset($_SESSION['import_data']['map']);
            return Horde_Data::IMPORT_MAPPED;

        default:
            return parent::nextStep($action, $param);
        }
    }

}
