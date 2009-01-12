<?php
/**
 * This class represent a month of free busy information sets.
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information.
 *
 * $Horde: kronolith/lib/FBView/month.php,v 1.8 2009/01/06 18:01:01 jan Exp $
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_FreeBusy_View_month extends Kronolith_FreeBusy_View {

    var $view = 'month';
    var $_days = 30;

    function _title()
    {
        global $registry, $prefs;

        $end = new Horde_Date($this->_start);
        $end->mday += $this->_days - 1;
        $end->correct();
        $prev = new Horde_Date($this->_start);
        $prev->month--;
        $prev->correct();
        $next = new Horde_Date($this->_start);
        $next->month++;
        $next->correct();
        return Horde::link('#', _("Previous Month"), '', '', 'return switchDate(' . $prev->dateString() . ');')
            . Horde::img('nav/left.png', '<', null, $registry->getImageDir('horde'))
            . '</a>'
            . $this->_start->strftime('%B %Y')
            . Horde::link('#', _("Next Month"), '', '', 'return switchDate(' . $next->dateString() . ');')
            . Horde::img('nav/right.png', '>', null, $registry->getImageDir('horde'))
            . '</a>';
    }

    function _hours()
    {
        global $prefs;

        $hours_html = '';
        $dayWidth = round(100 / $this->_days);
        $date_format = $prefs->getValue('date_format');

        $week = Date_Calc::weekOfYear(1, $this->_start->month, $this->_start->year);
        $span = (7 - $week) % 7 + 1;
        $span_left = $this->_days;
        $t = new Horde_Date($this->_start);
        while ($span_left > 0) {
            $span_left -= $span;
            $week_label = Horde::link('#', '', '', '', 'return switchDateView(\'week\',' . $t->dateString() . ');') . ("Week") . ' ' . $week . '</a>';
            $hours_html .= sprintf('<th colspan="%d" width="%s%%">%s</th>',
                                   $span, $dayWidth, $week_label);
            $week++;
            $t->mday += 7;
            $t->correct();
            $span = min($span_left, 7);
        }
        $hours_html .= '</tr><tr><td width="100" class="label">&nbsp;</td>';

        for ($i = 0; $i < $this->_days; $i++) {
            $t = new Horde_Date(array('month' => $this->_start->month,
                                      'mday' => $this->_start->mday + $i,
                                      'year' => $this->_start->year));
            $day_label = Horde::link('#', '', '', '', 'return switchDateView(\'day\',' . $t->dateString() . ');') . sprintf("%s.", $i + 1) . '</a>';
            $hours_html .= sprintf('<th width="%s%%">%s</th>',
                                   $dayWidth, $day_label);
        }

        for ($i = 0; $i < $this->_days; $i++) {
            $start = new Horde_Date(array('hour' => $this->_startHour,
                                          'month' => $this->_start->month,
                                          'mday' => $this->_start->mday + $i,
                                          'year' => $this->_start->year));
            $end = new Horde_Date(array('hour' => $this->_endHour,
                                        'month' => $this->_start->month,
                                        'mday' => $this->_start->mday + $i,
                                        'year' => $this->_start->year));
            $this->_timeBlocks[] = array($start, $end);
        }

        return $hours_html;
    }

    function _render($day = null)
    {
        $this->_start = new Horde_Date($day);
        $this->_start->mday = 1;
        $this->_days = Horde_Date::daysInMonth($day->month, $day->year);
        $this->_end = new Horde_Date($this->_start);
        $this->_end->hour = 23;
        $this->_end->min = $this->_end->sec = 59;
        $this->_end->mday = $this->_days;
    }

}
