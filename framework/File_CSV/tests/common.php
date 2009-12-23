<?php
/**
 * @package File_CSV
 */

require_once dirname(__FILE__) . '/../CSV.php';

function test_csv()
{
    foreach (func_get_args() as $file) {
        $file = dirname(__FILE__) . '/' . $file . '.csv';
        $conf = File_CSV::discoverFormat($file);
        if (is_a($conf, 'PEAR_Error')) {
            var_dump($conf);
            return;
        }
        $csv = array();
        while ($row = File_CSV::read($file, $conf)) {
            if (is_a($row, 'PEAR_Error')) {
                var_dump($row);
                return;
            }
            $csv[] = $row;
        }
        var_dump($csv);
        $warnings = File_CSV::warning();
        if (count($warnings)) {
            var_dump($warnings);
        }
    }
}
