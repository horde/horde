<?php
/**
 * @category Horde
 * @package  Data
 */

/**
 * Horde_Data implementation for comma-separated data (CSV).
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data_Csv extends Horde_Data_Base
{
    /**
     * MIME content type.
     *
     * @var string
     */
    protected $_contentType = 'application/csv';

    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = 'csv';

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
     * @throws Horde_Data_Exception
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
            'length' => $fields,
            'quote' => $quote,
            'separator' => $sep
        );

        $fp = @fopen($filename, 'r');
        if (!$fp) {
            throw new Horde_Data_Exception(Horde_Data_Translation::t("Cannot open file."));
        }

        /* Strip and keep the first line if it contains the field names. */
        if ($header) {
            $head = self::getCsv($fp, $conf);
            if (!$head) {
                return array();
            }
            if (!empty($charset)) {
                $head = Horde_String::convertCharset($head, $charset, 'UTF-8');
            }
        }

        $data = array();
        while ($line = self::getCsv($fp, $conf)) {
            if (!empty($charset)) {
                $line = Horde_String::convertCharset($line, $charset, 'UTF-8');
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
                    $export .= '"' . str_replace('"', '\\"', $key) . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . $eol;
        }

        foreach ($data as $row) {
            foreach ($head as $key) {
                $cell = $row[$key];
                if (!empty($cell) || $cell === 0) {
                    $export .= '"' . str_replace('"', '\\"', $cell) . '"';
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
        if (!isset($this->_browser)) {
            throw new LogicException('Missing browser parameter.');
        }

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
     *                         parameters for the current step. Keys for this
     *                         driver:
     *   - check_charset: (boolean) Do some checks to see if the correct
     *                    charset has been provided. Throws charset exception
     *                    on error.
     *   - import_mapping: TODO
     *
     * @return mixed  Either the next step as an integer constant or imported
     *                data set after the final step.
     * @throws Horde_Data_Exception
     * @throws Horde_Data_Exception_Charset
     */
    public function nextStep($action, array $param = array())
    {
        switch ($action) {
        case Horde_Data::IMPORT_FILE:
            parent::nextStep($action, $param);

            /* Move uploaded file so that we can read it again in the next
               step after the user gave some format details. */
            $file_name = Horde_Util::getTempFile('import', false);
            if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_name)) {
                throw new Horde_Data_Exception(Horde_Data_Translation::t("The uploaded file could not be saved."));
            }

            /* Do charset checking now, if requested. */
            if (isset($param['check_charset'])) {
                $file_data = file_get_contents($file_name);
                $charset = isset($this->_vars->charset)
                    ? Horde_String::lower($this->_vars->charset)
                    : 'utf-8';

                switch ($charset) {
                case 'utf-8':
                    $error = !Horde_String::validUtf8($file_data);
                    break;

                default:
                    $error = ($file_data != Horde_String::convertCharset(Horde_String::convertCharset($file_data, $charset, 'UTF-8'), 'UTF-8', $charset));
                    break;
                }

                if ($error) {
                    $e = new Horde_Data_Exception_Charset(Horde_Data_Translation::t("Incorrect charset given for the data."));
                    $e->badCharset = $charset;
                    throw $e;
                }
            }

            $this->storage->set('charset', $this->_vars->charset);
            $this->storage->set('file_name', $file_name);

            /* Read the file's first two lines to show them to the user. */
            $first_lines = '';
            if ($fp = @fopen($file_name, 'r')) {
                for ($line_no = 1, $line = fgets($fp);
                     $line_no <= 3 && $line;
                     $line_no++, $line = fgets($fp)) {
                    $line = Horde_String::convertCharset($line, $this->_vars->charset, 'UTF-8');
                    $first_lines .= Horde_String::truncate($line);
                    if (Horde_String::length($line) > 100) {
                        $first_lines .= "\n";
                    }
                }
            }
            $this->storage->set('first_lines', $first_lines);

            /* Import the first line to guess the number of fields. */
            if ($first_lines) {
                rewind($fp);
                $line = self::getCsv($fp);
                if ($line) {
                    $this->storage->set('fields', count($line));
                }
            }

            return Horde_Data::IMPORT_CSV;

        case Horde_Data::IMPORT_CSV:
            $this->storage->set('header', $this->_vars->header);
            $import_mapping = array();
            if (isset($param['import_mapping'])) {
                $import_mapping = $param['import_mapping'];
            }
            $this->storage->set('data', $this->importFile(
                $this->storage->get('file_name'),
                $this->_vars->header,
                $this->_vars->sep,
                $this->_vars->quote,
                $this->_vars->fields,
                $import_mapping,
                $this->storage->get('charset'),
                $this->storage->get('crlf')
            ));
            $this->storage->set('map');
            return Horde_Data::IMPORT_MAPPED;

        default:
            return parent::nextStep($action, $param);
        }
    }

    /* Static utility method. */

    /**
     * Wrapper around fgetcsv().
     *
     * Empty lines will be skipped. If the 'length' parameter is provided, all
     * rows are filled up with empty strings up to this length, or stripped
     * down to this length.
     *
     * @param resource $file  A file pointer.
     * @param array $params   Optional parameters. Possible values:
     *   - escape: The escape character.
     *   - length: The expected number of fields.
     *   - quote: The quote character.
     *   - separator: The field delimiter.
     *
     * @return array|boolean  A row from the CSV file or false on error or end
     *                        of file.
     */
    static public function getCsv($file, array $params = array())
    {
        $params += array(
            'escape' => '\\',
            'quote' => '"',
            'separator' => ','
        );

        // fgetcsv() throws a warning if the quote character is empty.
        if (!strlen($params['quote']) && ($params['escape'] != '\\')) {
            $params['quote'] = '"';
        }

        // Detect Mac line endings.
        $old = ini_get('auto_detect_line_endings');
        ini_set('auto_detect_line_endings', 1);

        do {
            $row = strlen($params['quote'])
                ? fgetcsv($file, 0, $params['separator'], $params['quote'], $params['escape'])
                : fgetcsv($file, 0, $params['separator']);
        } while ($row && is_null($row[0]));

        ini_set('auto_detect_line_endings', $old);

        if ($row) {
            $row = (strlen($params['quote']) && strlen($params['escape']))
                ? array_map(create_function('$a', 'return str_replace(\'' . str_replace('\'', '\\\'', $params['escape'] . $params['quote']) . '\', \'' . str_replace('\'', '\\\'', $params['quote']) . '\', $a);'), $row)
                : array_map('trim', $row);

            if (!empty($params['length'])) {
                $length = count($row);
                if ($length < $params['length']) {
                    $row += array_fill($length, $params['length'] - $length, '');
                } elseif ($length > $params['length']) {
                    array_splice($row, $params['length']);
                }
            }
        }

        return $row;
    }

}
