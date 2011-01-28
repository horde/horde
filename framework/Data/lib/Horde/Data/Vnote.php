<?php
/**
 * Implement the Horde_Data:: API for vNote data.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data_Vnote extends Horde_Data_Imc
{
    /**
     * Exports vcalendar data as a string. Unlike vEvent, vNote data
     * is not enclosed in BEGIN|END:vCalendar.
     *
     * @param array $data     An array containing Horde_Icalendar_Vnote
     *                        objects.
     * @param string $method  The iTip method to use.
     *
     * @return string  The iCalendar data.
     */
    public function exportData($data, $method = 'REQUEST')
    {
        $this->_iCal = new Horde_Icalendar();
        $this->_iCal->setAttribute('METHOD', $method);

        $s = '';
        foreach ($data as $event) {
            $s. = $event->exportvCalendar();
        }

        return $s;
    }

}
