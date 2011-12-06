<?php
/**
 * The Hermes_Data_Hermescsv class extends Horde's CSV Data class with
 * Hermes-specific handling.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Data
 */
class Hermes_Data_Hermescsv extends Horde_Data_Csv
{
    public function exportData(
        $data, $header = true, $export_mapping = array())
    {
        return parent::exportData($this->_map($data), $header, $export_mapping);
    }

    protected function _map($data)
    {
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $data[$i] = $data[$i]->toArray();
            $data[$i]['description'] = str_replace(array("\r", "\n"), array('', ' '), $data[$i]['description']);
            $data[$i]['note'] = str_replace(array("\r", "\n"), array('', ' '), $data[$i]['note']);
            $data[$i]['timestamp'] = $data[$i]['date'];
            $data[$i]['date'] = date('m/d/y', $data[$i]['date']);
            $data[$i]['duration'] = date('H:i', mktime(0, $data[$i]['hours'] * 60));
            $data[$i]['billable'] = $data[$i]['billable'] == 2 ? '' : $data[$i]['billable'];
        }

        return $data;
    }

}
