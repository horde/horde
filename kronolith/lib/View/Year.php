<?php
/**
 * The Kronolith_View_Year:: class provides an API for viewing years.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Year {

    var $year;
    var $_events = array();

    function Kronolith_View_Year($date)
    {
        $this->year = $date->year;
        $startDate = new Horde_Date(array('year' => $this->year,
                                          'month' => 1,
                                          'mday' => 1));
        $endDate = new Horde_Date(array('year' => $this->year,
                                        'month' => 12,
                                        'mday' => 31));

        try {
            $this->_events = Kronolith::listEvents($startDate, $endDate, $GLOBALS['display_calendars']);
        } catch (Exception $e) {
            $GLOBALS['notification']->push($e, 'horde.error');
            $this->_events = array();
        }
        if (!is_array($this->_events)) {
            $this->_events = array();
        }
    }

    function html()
    {
        global $prefs;

        require KRONOLITH_TEMPLATES . '/year/head.inc';

        $html = '<table class="nopadding" cellspacing="5" width="100%"><tr>';
        for ($month = 1; $month <= 12; ++$month) {
            $html .= '<td valign="top">';

            // Heading for each month.
            $date = new Horde_Date(sprintf('%04d%02d01010101', $this->year, $month));
            $mtitle = $date->strftime('%B');
            $html .= '<h2 class="smallheader"><a class="smallheader" href="'
                . Horde::url('month.php')
                ->add('date', $date->dateString())
                . '">' . $mtitle . '</a></h2>'
                . '<table class="nopadding monthgrid" cellspacing="0" width="100%"><thead><tr class="item">';
            if (!$prefs->getValue('week_start_monday')) {
                $html .= '<th>' . _("Su"). '</th>';
            }
            $html .= '<th>' . _("Mo") . '</th>' .
                '<th>' . _("Tu") . '</th>' .
                '<th>' . _("We") . '</th>' .
                '<th>' . _("Th") . '</th>' .
                '<th>' . _("Fr") . '</th>' .
                '<th>' . _("Sa") . '</th>';
            if ($prefs->getValue('week_start_monday')) {
                $html .= '<th>' . _("Su") . '</th>';
            }
            $html .= '</tr></thead><tbody><tr>';

            $startday = new Horde_Date(array('mday' => 1,
                                             'month' => $month,
                                             'year' => $this->year));
            $startday = $startday->dayOfWeek();

            $daysInView = Date_Calc::weeksInMonth($month, $this->year) * 7;
            if (!$prefs->getValue('week_start_monday')) {
                $startOfView = 1 - $startday;

                // We may need to adjust the number of days in the
                // view if we're starting weeks on Sunday.
                if ($startday == Horde_Date::DATE_SUNDAY) {
                    $daysInView -= 7;
                }
                $endday = new Horde_Date(array('mday' => Horde_Date_Utils::daysInMonth($month, $this->year),
                                               'month' => $month,
                                               'year' => $this->year));
                $endday = $endday->dayOfWeek();
                if ($endday == Horde_Date::DATE_SUNDAY) {
                    $daysInView += 7;
                }
            } else {
                if ($startday == Horde_Date::DATE_SUNDAY) {
                    $startOfView = -5;
                } else {
                    $startOfView = 2 - $startday;
                }
            }

            $currentCalendars = array(true);
            foreach ($currentCalendars as $id => $cal) {
                $cell = 0;
                for ($day = $startOfView; $day < $startOfView + $daysInView; ++$day) {
                    $date = new Kronolith_Day($month, $day, $this->year);
                    $date->hour = $prefs->getValue('twentyFour') ? 12 : 6;
                    $week = $date->weekOfYear();

                    if ($cell % 7 == 0 && $cell != 0) {
                        $html .= "</tr>\n<tr>";
                    }
                    if ($date->month != $month) {
                        $style = 'monthgrid';
                    } elseif ($date->dayOfWeek() == 0 || $date->dayOfWeek() == 6) {
                        $style = 'weekend';
                    } else {
                        $style = 'text';
                    }

                    /* Set up the link to the day view. */
                    $url = Horde::url('day.php', true)
                        ->add('date', $date->dateString());

                    if ($date->month != $month) {
                        $cellday = '&nbsp;';
                    } elseif (!empty($this->_events[$date->dateString()])) {
                        /* There are events; create a cell with tooltip to list
                         * them. */
                        $day_events = '';
                        foreach ($this->_events[$date->dateString()] as $event) {
                            if ($event->status == Kronolith::STATUS_CONFIRMED) {
                                /* Set the background color to distinguish the
                                 * day */
                                $style = 'year-event';
                            }

                            if ($event->isAllDay()) {
                                $day_events .= _("All day");
                            } else {
                                $day_events .= $event->start->strftime($prefs->getValue('twentyFour') ? '%R' : '%I:%M%p') . '-' . $event->end->strftime($prefs->getValue('twentyFour') ? '%R' : '%I:%M%p');
                            }
                            $day_events .= ':'
                                . (($event->location) ? ' (' . $event->location . ')' : '')
                                . ' ' . $event->getTitle() . "\n";
                        }
                        /* Bold the cell if there are events. */
                        $cellday = '<strong>' . Horde::linkTooltip($url, _("View Day"), '', '', '', $day_events) . $date->mday . '</a></strong>';
                    } else {
                        /* No events, plain link to the day. */
                        $cellday = Horde::linkTooltip($url, _("View Day")) . $date->mday . '</a>';
                    }
                    if ($date->isToday() && $date->month == $month) {
                        $style .= ' today';
                    }

                    $html .= '<td align="center" class="' . $style . '" height="10" width="5%" valign="top">' .
                        $cellday . '</td>';
                    ++$cell;
                }
            }

            $html .= '</tr></tbody></table></td>';
            if ($month % 3 == 0 && $month != 12) {
                $html .= '</tr><tr>';
            }
        }

        echo $html . '</tr></table>';
    }

    function link($offset = 0, $full = false)
    {
        return Horde::url('year.php', $full)
            ->add('date', ($this->year + $offset) . '0101');
    }

    function getName()
    {
        return 'Year';
    }

}
