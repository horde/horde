<?php
/**
 * Abstract class that Data drivers extend.
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
abstract class Horde_Data_Base
{
    /**
     * Storage object.
     *
     * @var Horde_Data_Storage
     */
    public $storage;

    /**
     * Browser object.
     *
     * @var Horde_Browser
     */
    protected $_browser;

    /**
     * Cleanup callback function.
     *
     * @var callback
     */
    protected $_cleanupCallback;

    /**
     * MIME content type.
     *
     * @var string
     */
    protected $_contentType = 'text/plain';

    /**
     * File extension.
     *
     * @var string
     */
    protected $_extension = '';

    /**
     * Variables object.
     *
     * @var Horde_Variables
     */
    protected $_vars;

    /**
     * A list of warnings raised during the last operation.
     *
     * @var array
     */
    protected $_warnings = array();

    /**
     * Constructor.
     *
     * @param Horde_Data_Storage  A storage object.
     * @param array $params       Optional parameters:
     *   - browser: (Horde_Browser) A browser object.
     *   - cleanup: (callback) A callback to call at cleanup time.
     *   - vars: (Horde_Variables) Form data.
     */
    public function __construct(Horde_Data_Storage $storage,
                                array $params = array())
    {
        $this->storage = $storage;

        if (isset($params['browser'])) {
            $this->_browser = $params['browser'];
        }

        if (isset($params['cleanup']) && is_callable($params['cleanup'])) {
            $this->_cleanupCallback = $params['cleanup'];
        }

        $this->_vars = isset($params['vars'])
            ? $params['vars']
            : Horde_Variables::getDefaultVariables();
    }

    /**
     * Stub to import passed data.
     */
    public function importData($text)
    {
    }

    /**
     * Stub to return exported data.
     */
    abstract public function exportData($data, $method = 'REQUEST');

    /**
     * Stub to import a file.
     */
    public function importFile($filename, $header = false)
    {
        $data = file_get_contents($filename);
        return $this->importData($data, $header);
    }

    /**
     * Stub to export data to a file.
     */
    abstract public function exportFile($filename, $data);

    /**
     * Tries to determine the expected newline character based on the
     * platform information passed by the browser's agent header.
     *
     * @return string  The guessed expected newline characters, either \n, \r
     *                 or \r\n.
     */
    public function getNewline()
    {
        if (!isset($this->_browser)) {
            throw new LogicException('Missing browser parameter.');
        }

        switch ($this->_browser->getPlatform()) {
        case 'win':
            return "\r\n";

        case 'mac':
            return "\r";

        case 'unix':
        default:
            return "\n";
        }
    }

    /**
     * Returns the full filename including the basename and extension.
     *
     * @param string $basename  Basename for the file.
     *
     * @return string  The file name.
     */
    public function getFilename($basename)
    {
        return $basename . '.' . $this->_extension;
    }

    /**
     * Returns the content type.
     *
     * @return string  The content type.
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * Returns a list of warnings that have been raised during the last
     * operation.
     *
     * @return array  A (possibly empty) list of warnings.
     */
    public function warnings()
    {
        return $this->_warnings;
    }

    /**
     * Maps a date/time string to an associative array.
     *
     * @param string $date   The date.
     * @param string $type   One of 'date', 'time' or 'datetime'.
     * @param array $params  Two-dimensional array with additional information
     *                       about the formatting. Possible keys are:
     *   - delimiter: The character that seperates the different date/time
     *                parts.
     *   - format: If 'ampm' and $date contains a time we assume that it is in
     *             AM/PM format.
     *   - order: If $type is 'datetime' the order of the day and time parts:
     *           -1 (timestamp), 0 (day/time), 1 (time/day).
     * @param integer $key   The key to use for $params.
     *
     * @return string  The date or time in ISO format.
     */
    protected function _mapDate($date, $type, $params, $key)
    {
        switch ($type) {
        case 'date':
        case 'monthday':
        case 'monthdayyear':
            $dates = explode($params['delimiter'][$key], $date);
            if (count($dates) != 3) {
                return $date;
            }
            $index = array_flip(explode('/', $params['format'][$key]));
            return $dates[$index['year']] . '-' . $dates[$index['month']] . '-' . $dates[$index['mday']];

        case 'time':
            $dates = explode($params['delimiter'][$key], $date);
            if (count($dates) < 2 || count($dates) > 3) {
                return $date;
            }
            if ($params['format'][$key] == 'ampm') {
                if (strpos(strtolower($dates[count($dates)-1]), 'pm') !== false) {
                    if ($dates[0] !== '12') {
                        $dates[0] += 12;
                    }
                } elseif ($dates[0] == '12') {
                    $dates[0] = '0';
                }
                $dates[count($dates) - 1] = sprintf('%02d', $dates[count($dates)-1]);
            }
            return $dates[0] . ':' . $dates[1] . (count($dates) == 3 ? (':' . $dates[2]) : ':00');

        case 'datetime':
            switch ($params['order'][$key]) {
            case -1:
                return (string)(int)$date == $date
                    ? date('Y-m-d H:i:s', $date)
                    : $date;
            case 0:
                list($day, $time) = explode(' ', $date, 2);
                break;
            case 1:
               list($time, $day) = explode(' ', $date, 2);
               break;
            }
            $date = $this->_mapDate($day, 'date', array(
                'delimiter' => $params['day_delimiter'],
                'format' => $params['day_format']
            ), $key);
            $time = $this->_mapDate($time, 'time', array(
                'delimiter' => $params['time_delimiter'],
                'format' => $params['time_format']
            ), $key);

            return $date . ' ' . $time;
        }
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
        /* First step. */
        if (is_null($action)) {
            return Horde_Data::IMPORT_FILE;
        }

        switch ($action) {
        case Horde_Data::IMPORT_FILE:
            if (!isset($this->_browser)) {
                throw new LogicException('Missing browser parameter.');
            }
            /* Sanitize uploaded file. */
            try {
                $this->_browser->wasFileUploaded('import_file', $param['file_types'][$this->_vars->import_format]);
            } catch (Horde_Exception $e) {
                throw new Horde_Data_Exception($e);
            }
            if ($_FILES['import_file']['size'] <= 0) {
                throw new Horde_Data_Exception(Horde_Data_Translation::t("The file contained no data."));
            }
            $this->storage->set('format', $this->_vars->import_format);
            break;

        case Horde_Data::IMPORT_MAPPED:
            if (!$this->_vars->dataKeys || !$this->_vars->appKeys) {
                throw new Horde_Data_Exception(Horde_Data_Translation::t("You didn\'t map any fields from the imported file to the corresponding fields."));
            }
            $dataKeys = explode("\t", $this->_vars->dataKeys);
            $appKeys = explode("\t", $this->_vars->appKeys);
            $dates = $map = array();

            if (!$import_data = $this->storage->get('data')) {
                $import_data = array();
            }

            foreach ($appKeys as $key => $app) {
                $map[$dataKeys[$key]] = $app;
                if (isset($param['time_fields']) &&
                    isset($param['time_fields'][$app])) {
                    $dates[$dataKeys[$key]]['type'] = $param['time_fields'][$app];
                    $dates[$dataKeys[$key]]['values'] = array();
                    $i = 0;
                    /* Build an example array of up to 10 date/time fields. */
                    while ($i < count($import_data) &&
                           count($dates[$dataKeys[$key]]['values']) < 10) {
                        if (!empty($import_data[$i][$dataKeys[$key]])) {
                            $dates[$dataKeys[$key]]['values'][] = $import_data[$i][$dataKeys[$key]];
                        }
                        ++$i;
                    }
                }
            }

            $this->storage->set('map', $map);
            if (count($dates) > 0) {
                foreach ($dates as $key => $data) {
                    if (count($data['values'])) {
                        $this->storage->set('dates', $dates);
                        return Horde_Data::IMPORT_DATETIME;
                    }
                }
            }
            return $this->nextStep(Horde_Data::IMPORT_DATA, $param);

        case Horde_Data::IMPORT_DATETIME:
        case Horde_Data::IMPORT_DATA:
            if ($action == Horde_Data::IMPORT_DATETIME) {
                $params = array(
                    'delimiter' => $this->_vars->delimiter,
                    'format' => $this->_vars->format,
                    'order' => $this->_vars->order,
                    'day_delimiter' => $this->_vars->day_delimiter,
                    'day_format' => $this->_vars->day_format,
                    'time_delimiter' => $this->_vars->time_delimiter,
                    'time_format' => $this->_vars->time_format
                );
            }

            if (!$this->storage->exists('data')) {
                throw new Horde_Data_Exception(Horde_Data_Translation::t("The uploaded data was lost since the previous step."));
            }

            /* Build the result data set as an associative array. */
            $data = array();
            $data_map = $this->storage->get('map');

            foreach ($this->storage->get('data') as $row) {
                $data_row = array();
                foreach ($row as $key => $val) {
                    if (isset($data_map[$key])) {
                        $mapped_key = $data_map[$key];
                        if ($action == Horde_Data::IMPORT_DATETIME &&
                            !empty($val) &&
                            isset($param['time_fields']) &&
                            isset($param['time_fields'][$mapped_key])) {
                            $val = $this->_mapDate($val, $param['time_fields'][$mapped_key], $params, $key);
                        }
                        $data_row[$mapped_key] = $val;
                    }
                }
                $data[] = $data_row;
            }

            return $data;
        }
    }

    /**
     * Removes any uploaded and moved files.
     *
     * @return mixed  If callback called, the return value of this call.
     *                This should be the value of the first import step.
     */
    public function cleanup()
    {
        if ($filename = $this->storage->get('file_name')) {
            @unlink($filename);
        }
        $this->storage->clear();

        if ($this->_cleanupCallback) {
            return call_user_func($this->_cleanupCallback);
        }
    }

}
