<?php
/**
 * Abstract implementation of the Horde_Data:: API for IMC data -
 * vCards and iCalendar data, etc. Provides a number of utility
 * methods that vCard and iCalendar implementation can share and rely
 * on.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data_Imc extends Horde_Data_Base
{
    /**
     * @var
     */
    protected $_iCal = false;

    /**
     *
     * @throws Horde_Data_Exception
     */
    public function importData($text)
    {
        $this->_iCal = new Horde_Icalendar();
        if (!$this->_iCal->parsevCalendar($text)) {
            throw new Horde_Data_Exception('There was an error importing the iCalendar data.');
        }

        return $this->_iCal->getComponents();
    }

    /**
     * Builds an iCalendar file from a given data structure and
     * returns it as a string.
     *
     * @param array $data     An array containing Horde_Icalendar_Vevent
     *                        objects
     * @param string $method  The iTip method to use.
     *
     * @return string  The iCalendar data.
     */
    public function exportData($data, $method = 'REQUEST')
    {
        $this->_iCal = new Horde_Icalendar();
        $this->_iCal->setAttribute('METHOD', $method);

        foreach ($data as $event) {
            $this->_iCal->addComponent($event);
        }

        return $this->_iCal->exportvCalendar();
    }

    /**
     * Builds an iCalendar file from a given data structure and
     * triggers its download.  It DOES NOT exit the current script but
     * only outputs the correct headers and data.
     *
     * @param string $filename   The name of the file to be downloaded.
     * @param array $data        An array containing Horde_Icalendar_Vevents
     */
    public function exportFile($filename, $data)
    {
        if (!isset($this->_browser)) {
            throw new Horde_Data_Exception('Missing browser parameter.');
        }

        $export = $this->exportData($data);
        $this->_browser->downloadHeaders($filename, 'text/calendar', false, strlen($export));
        echo $export;
    }

    /**
     * Takes all necessary actions for the given import step,
     * parameters and form values and returns the next necessary step.
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
            $this->importFile($_FILES['import_file']['tmp_name']);
            return $this->_iCal->getComponents();
        }

        return parent::nextStep($action, $param);
    }

}
