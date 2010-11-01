<?php
/**
 * This class represent a single day of free busy information sets.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_FreeBusy_View_Day extends Kronolith_FreeBusy_View {

    var $view = 'day';

    function _title()
    {
        global $registry, $prefs;

        $prev = new Horde_Date($this->_start);
        $prev->mday--;
        $next = new Horde_Date($this->_start);
        $next->mday++;
        return Horde::url('#')->link(array('title' => _("Previous Day"), 'onclick' => 'return switchDate(' . $prev->dateString() . ');'))
            . Horde::img('nav/left.png', '<')
            . '</a>'
            . $this->_start->strftime($prefs->getValue('date_format'))
            . Horde::url('#')->link(array('title' => _("Next Day"), 'onclick' => 'return switchDate(' . $next->dateString() . ');'))
            . Horde::img('nav/right.png', '>')
            . '</a>';
    }

    function _hours()
    {
        global $prefs;

        $hours_html = '';
        $width = round(100 / ($this->_endHour - $this->_startHour + 1));
        $start = new Horde_Date($this->_start);
        $end = new Horde_Date($this->_start);
        $end->min = 59;
        for ($i = $this->_startHour; $i < $this->_endHour; $i++) {
            $start->hour = $end->hour = $i;
            $this->_timeBlocks[] = array(clone $start, clone $end);
            $hours_html .= '<th width="' . $width . '%">' . $start->strftime($prefs->getValue('twentyFour') ? '%H:00' : '%I:00') . '</th>';
        }

        return $hours_html;
    }

    function _render($day = null)
    {
        $this->_start = new Horde_Date($day);
        $this->_start->hour = $this->_startHour;
        $this->_end = new Horde_Date($this->_start);
        $this->_end->hour = $this->_endHour;
    }

}
