<?php
/**
 * Horde_Data implementation for Outlook comma-separated data (CSV).
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
class Horde_Data_Outlookcsv extends Horde_Data_Csv
{
    /**
     * Builds a CSV file from a given data structure and returns it as a
     * string.
     *
     * @param array $data      A two-dimensional array containing the data
     *                         set.
     * @param boolean $header  If true, the rows of $data are associative
     *                         arrays with field names as their keys.
     *
     * @return string  The CSV data.
     */
    public function exportData($data, $header = false,
                               $export_mapping = array())
    {
        if (!is_array($data) || (count($data) == 0)) {
            return '';
        }

        $export = '';
        $eol = "\r\n";
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
                    $cell = preg_replace("/\"/", "\"\"", $cell);
                    $export .= '"' . $cell . '"';
                }
                $export .= ',';
            }
            $export = substr($export, 0, -1) . $eol;
        }

        return $export;
    }

}
