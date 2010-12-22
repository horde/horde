<?php
/**
 * The Horde_Data_hermes_csv class extends Horde's CSV Data class with
 * Hermes-specific handling.
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Data
 */
class Horde_Data_hermes_csv extends Horde_Data_csv {

    var $_mapped = false;

    function exportData($data)
    {
        return parent::exportData($this->_map($data), true);
    }

    function _map($data)
    {
        if ($this->_mapped) {
            return $data;
        }

        $this->_mapped = true;

        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
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
