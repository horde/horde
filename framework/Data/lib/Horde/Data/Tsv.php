<?php
/**
 * Horde_Data implementation for tab-separated data (TSV).
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
class Horde_Data_Tsv extends Horde_Data_Base
{
    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = 'tsv';

    /**
     * MIME content type.
     *
     * @var string
     */
    protected $_contentType = 'text/tab-separated-values';

    /**
     * Convert data file contents to list of data records.
     *
     * @param string $contents   Data file contents.
     * @param boolean $header    True if a header row is present.
     * @param string $delimiter  Field delimiter.
     *
     * @return array  List of data records.
     */
    public function importData($contents, $header = false, $delimiter = "\t")
    {
        if ($this->storage->get('format') == 'pine') {
            $contents = preg_replace('/\n +/', '', $contents);
        }

        $contents = explode("\n", $contents);
        $data = array();
        if ($header) {
            $head = explode($delimiter, array_shift($contents));
        }

        foreach ($contents as $line) {
            if (trim($line) == '') {
                continue;
            }
            $line = explode($delimiter, $line);
            if (!isset($head)) {
                $data[] = $line;
            } else {
                $newline = array();
                for ($i = 0; $i < count($head); $i++) {
                    $newline[$head[$i]] = empty($line[$i]) ? '' : $line[$i];
                }
                $data[] = $newline;
            }
        }

        return $data;
    }

    /**
     * Builds a TSV file from a given data structure and returns it as a
     * string.
     *
     * @param array $data      A two-dimensional array containing the data set.
     * @param boolean $header  If true, the rows of $data are associative
     *                         arrays with field names as their keys.
     *
     * @return string  The TSV data.
     */
    public function exportData($data, $header = false)
    {
        if (!is_array($data) || count($data) == 0) {
            return '';
        }
        $export = '';
        $head = array_keys(current($data));
        if ($header) {
            $export = implode("\t", $head) . "\n";
        }
        foreach ($data as $row) {
            foreach ($head as $key) {
                $cell = $row[$key];
                if (!empty($cell) || $cell === 0) {
                    $export .= $cell;
                }
                $export .= "\t";
            }
            $export = substr($export, 0, -1) . "\n";
        }

        return $export;
    }

    /**
     * Builds a TSV file from a given data structure and triggers its download.
     * It DOES NOT exit the current script but only outputs the correct headers
     * and data.
     *
     * @param string $filename  The name of the file to be downloaded.
     * @param array $data       A two-dimensional array containing the data
     *                          set.
     * @param boolean $header   If true, the rows of $data are associative
     *                          arrays with field names as their keys.
     */
    public function exportFile($filename, $data, $header = false)
    {
        if (!isset($this->_browser)) {
            throw new LogicException('Missing browser parameter.');
        }

        $export = $this->exportData($data, $header);
        $this->_browser->downloadHeaders($filename, 'text/tab-separated-values', false, strlen($export));
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
    public function nextStep($action, array $param = array())
    {
        switch ($action) {
        case Horde_Data::IMPORT_FILE:
            parent::nextStep($action, $param);

            $format = $this->storage->get('format');
            if (in_array($format, array('mulberry', 'pine'))) {
                $filedata = $this->importFile($_FILES['import_file']['tmp_name']);

                switch ($format) {
                case 'mulberry':
                    $appKeys  = array('alias', 'name', 'email', 'company', 'workAddress', 'workPhone', 'homePhone', 'fax', 'notes');
                    $dataKeys = array(0, 1, 2, 3, 4, 5, 6, 7, 9);
                    break;

                case 'pine':
                    $appKeys = array('alias', 'name', 'email', 'notes');
                    $dataKeys = array(0, 1, 2, 4);
                    break;
                }

                foreach ($appKeys as $key => $app) {
                    $map[$dataKeys[$key]] = $app;
                }

                $data = array();
                foreach ($filedata as $row) {
                    $hash = array();

                    switch ($format) {
                    case 'mulberry':
                        if (preg_match("/^Grp:/", $row[0]) || empty($row[1])) {
                            continue;
                        }
                        $row[1] = preg_replace('/^([^,"]+),\s*(.*)$/', '$2 $1', $row[1]);
                        foreach ($dataKeys as $key) {
                            if (array_key_exists($key, $row)) {
                                $hash[$key] = stripslashes(preg_replace('/\\\\r/', "\n", $row[$key]));
                            }
                        }
                        break;

                    case 'pine':
                        if (count($row) < 3 || preg_match("/^#DELETED/", $row[0]) || preg_match("/[()]/", $row[2])) {
                            continue;
                        }
                        $row[1] = preg_replace('/^([^,"]+),\s*(.*)$/', '$2 $1', $row[1]);
                        /* Address can be a full RFC822 address */
                        $addr_ob = new Horde_Mail_Rfc822_Address($row[2]);
                        if (!$addr_ob->valid) {
                            continue;
                        }
                        $row[2] = $addr_ob->bare_address;
                        if (empty($row[1]) && !is_null($addr_ob->personal)) {
                            $row[1] = $addr_ob->personal;
                        }
                        foreach ($dataKeys as $key) {
                            if (array_key_exists($key, $row)) {
                                $hash[$key] = $row[$key];
                            }
                        }
                        break;
                    }

                    $data[] = $hash;
                }

                $this->storage->set('data', $data);
                $this->storage->set('map', $map);

                return $this->nextStep(Horde_Data::IMPORT_DATA, $param);
            }

            /* Move uploaded file so that we can read it again in the next step
               after the user gave some format details. */
            try {
                $this->_browser->wasFileUploaded('import_file', Horde_Data_Translation::t("TSV file"));
            } catch (Horde_Browser_Exception $e) {
                throw new Horde_Data_Exception($e);
            }
            $file_name = Horde_Util::getTempFile('import', false);
            if (!move_uploaded_file($_FILES['import_file']['tmp_name'], $file_name)) {
                throw new Horde_Data_Exception(Horde_Data_Translation::t("The uploaded file could not be saved."));
            }
            $this->storage->set('file_name', $file_name);

            /* Read the file's first two lines to show them to the user. */
            $first_lines = '';
            if ($fp = @fopen($file_name, 'r')) {
                $line_no = 1;
                while (($line_no < 3) && ($line = fgets($fp))) {
                    $newline = Horde_String::length($line) > 100 ? "\n" : '';
                    $first_lines .= substr($line, 0, 100) . $newline;
                    ++$line_no;
                }
            }
            $this->storage->set('first_lines', $first_lines);
            return Horde_Data::IMPORT_TSV;

        case Horde_Data::IMPORT_TSV:
            $this->storage->set('header', $this->_vars->header);
            $this->storage->set('data', $this->importFile($this->storage->get('file_name'), $this->storage->get('header')));
            $this->storage->set('map');
            return Horde_Data::IMPORT_MAPPED;
        }

        return parent::nextStep($action, $param);
    }

}
