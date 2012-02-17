<?php
/**
 * The Horde_Data_iif class implements the Horde_Data:: framework for
 * QuickBooks .iif files.
 *
 * Here's a sample header and row from a .iif file (it's
 * tab-delimited):
 *
 * !TIMEACT        DATE        JOB        EMP        ITEM        DURATION        NOTE        BILLINGSTATUS        PITEM
 * TIMEACT        07/30/02        A Company:Their Projec        Sylvester Employee        Programming        10:00                1        Not Applicable
 *
 * $Horde: hermes/lib/Data/iif.php,v 1.21 2009/01/06 17:50:09 jan Exp $
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Data
 */
class Hermes_Data_Iif extends Horde_Data_Base
{
    protected $_extension = 'iif';
    protected $_contentType = 'text/plain';
    protected $_rawData;
    protected $_iifData;
    protected $_mapped = false;

    public function exportData($data, $method = 'REQUEST')
    {
        $this->_rawData = $data;
        $newline = $this->getNewline();

        $this->_map();

        $data = "!TIMEACT\tDATE\tJOB\tEMP\tITEM\tDURATION\tNOTE\tBILLINGSTATUS\tPITEM$newline";
        foreach ($this->_iifData as $row) {
            $data .= implode("\t", $row) . $newline;
        }

        return $data;
    }

    public function exportFile($filename, $data)
    {
        if (!isset($this->_browser)) {
            throw new Horde_Data_Exception('Missing browser parameter.');
        }
        $export = $this->exportData($data);
        $this->_browser->downloadHeaders($filename, $this->_contentType, false, strlen($export));
        echo $export;
    }

    protected function _map()
    {
        if ($this->_mapped) {
            return;
        }

        $this->_mapped = true;

        foreach ($this->_rawData as &$row) {
            $row = $row->toArray();
            $row['description'] = str_replace(array("\r", "\n"), array('', ' '), $row['description']);
            $row['note'] = str_replace(array("\r", "\n"), array('', ' '), $row['note']);
            $this->_iifData[] = array(
                '_label' => 'TIMEACT',
                'DATE' => date('m/d/y', $row['date']),
                'JOB' => $row['client'],
                'EMP' => $row['employee'],
                'ITEM' => $row['item'],
                'DURATION' => date('H:i', mktime(0, $row['hours'] * 60)),
                'NOTE' => $row['description'] . (!empty($row['note']) ? _("; Notes: ") . $row['note'] : ''),
                'BILLINGSTATUS' => $row['billable'] == 2 ? '' : $row['billable'],
                'PITEM' => 'Not Applicable'
            );
        }
    }

}
