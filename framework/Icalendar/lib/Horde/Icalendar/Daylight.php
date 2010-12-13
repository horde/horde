<?php
/**
 * TODO
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Icalendar
 */
class Horde_Icalendar_Daylight extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'daylight';

    /**
     * TODO
     *
     * @param $data TODO
     */
    public function parsevCalendar($text, $base = 'VCALENDAR', $clear = true)
    {
        parent::parsevCalendar($text, 'DAYLIGHT');
    }

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        return $this->_exportvData('DAYLIGHT');
    }

}
