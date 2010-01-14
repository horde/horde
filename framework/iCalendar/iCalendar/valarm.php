<?php
/**
 * Class representing vAlarms.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @package Horde_iCalendar
 */
class Horde_iCalendar_valarm extends Horde_iCalendar
{
    /**
     * The component type of this class.
     *
     * @var string
     */
    var $type = 'vAlarm';

    function exportvCalendar()
    {
        return parent::_exportvData('VALARM');
    }

}
