<?php
/**
 * This class represent a week of free busy information sets.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_FreeBusy_View_Week extends Kronolith_FreeBusy_View
{
    /**
     * This view type
     *
     * @var string
     */
    public $view = 'week';

    /**
     * Number of days
     *
     * @var integer
     */
    protected $_days = 7;

    protected function _title()
    {
        global $prefs;

        $prev = new Horde_Date($this->_start);
        $prev->mday -= 7;
        $next = new Horde_Date($this->_start);
        $next->mday += 7;
        $end = new Horde_Date($this->_start);
        $end->mday += $this->_days - 1;
        return Horde::url('#')->link(array('title' => _("Previous Week"), 'onclick' => 'return switchDate(' . $prev->dateString() . ');'))
            . Horde::img('nav/left.png', '<')
            . '</a>'
            . $this->_start->strftime($prefs->getValue('date_format')) . ' - '
            . $end->strftime($prefs->getValue('date_format'))
            . Horde::url('#')->link(array('title' => _("Next Week"), 'onclick' => 'return switchDate(' . $next->dateString() . ');'))
            . Horde::img('nav/right.png', '>')
            . '</a>';
    }

    protected function _hours()
    {
        global $prefs;

        $hours_html = '';
        $dayWidth = round(100 / $this->_days);
        $span = floor(($this->_endHour - $this->_startHour) / 3);
        if (($this->_endHour - $this->_startHour) % 3) {
            $span++;
        }
        $date_format = $prefs->getValue('date_format');
        for ($i = 0; $i < $this->_days; $i++) {
            $t = new Horde_Date(array('month' => $this->_start->month,
                                      'mday' => $this->_start->mday + $i,
                                      'year' => $this->_start->year));
            $day_label = Horde::url('#')->link(array('onclick' => 'return switchDateView(\'Day\',' . $t->dateString() . ');'))
                . $t->strftime($date_format) . '</a>';
            $hours_html .= sprintf('<th colspan="%d" width="%s%%">%s</th>',
                                   $span, $dayWidth, $day_label);
        }
        $hours_html .= '</tr><tr><td width="100" class="label">&nbsp;</td>';

        $width = round(100 / ($span * $this->_days));
        for ($i = 0; $i < $this->_days; $i++) {
            for ($h = $this->_startHour; $h < $this->_endHour; $h += 3) {
                $start = new Horde_Date(array('hour' => $h,
                                              'month' => $this->_start->month,
                                              'mday' => $this->_start->mday + $i,
                                              'year' => $this->_start->year));
                $end = new Horde_Date($start);
                $end->hour += 2;
                $end->min = 59;
                $this->_timeBlocks[] = array($start, $end);

                $hour = $start->strftime($prefs->getValue('twentyFour') ? '%H:00' : '%I:00');
                $hours_html .= sprintf('<th width="%d%%">%s</th>', $width, $hour);
            }
        }

        return $hours_html;
    }

    protected function _render(Horde_Date $day = null)
    {
        $this->_start = new Horde_Date(Date_Calc::beginOfWeek($day->mday, $day->month, $day->year, '%Y%m%d000000'));
        $this->_end = new Horde_Date($this->_start);
        $this->_end->hour = 23;
        $this->_end->min = $this->_end->sec = 59;
        $this->_end->mday += $this->_days - 1;
    }

}
