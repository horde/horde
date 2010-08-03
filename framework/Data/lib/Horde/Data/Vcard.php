<?php
/**
 * Implement the Horde_Data:: API for vCard data.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @package  Data
 */
class Horde_Data_Vcard extends Horde_Data_Imc {

    /**
     * Exports vcalendar data as a string. Unlike vEvent, vCard data
     * is not enclosed in BEGIN|END:vCalendar.
     *
     * @param array $data     An array containing Horde_Icalendar_Vcard
     *                        objects.
     * @param string $method  The iTip method to use.
     *
     * @return string  The iCalendar data.
     */
    public function exportData($data, $method = 'REQUEST')
    {
        $s = '';

        foreach ($data as $vcard) {
            $s.= $vcard->exportvCalendar();
        }

        return $s;
    }

}
