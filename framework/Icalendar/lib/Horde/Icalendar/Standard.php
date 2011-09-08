<?php
/**
 * Class representing vTimezones.
 *
 * Copyright 2003-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Icalendar
 */
class Horde_Icalendar_Standard extends Horde_Icalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    public $type = 'standard';

    /**
     * TODO
     *
     * @param $data TODO
     */
    public function parsevCalendar($text, $base = 'VCALENDAR', $clear = true)
    {
        parent::parsevCalendar($text, 'STANDARD');
    }

    /**
     * TODO
     *
     * @return TODO
     */
    public function exportvCalendar()
    {
        return $this->_exportvData('STANDARD');
    }

}
