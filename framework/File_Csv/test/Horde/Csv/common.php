<?php
/**
 * @package Horde_File_Csv
 */

require_once dirname(__FILE__) . '/../../../lib/Horde/Csv.php';

function test_csv()
{
    foreach (func_get_args() as $file) {
        $file = dirname(__FILE__) . '/' . $file . '.csv';
        try {
            $conf = Horde_File_Csv::discoverFormat($file);
        } catch (Horde_File_Csv_Exception $e) {
            var_dump($conf);
            return;
        }

        $csv = array();
        try {
            while ($row = Horde_File_Csv::read($file, $conf)) {
                $csv[] = $row;
            }
        } catch (Horde_File_Csv_Exception $e) {
            var_dump($row);
            return;
        }

        var_dump($csv);

        $warnings = Horde_File_Csv::warning();
        if (count($warnings)) {
            var_dump($warnings);
        }
    }
}
