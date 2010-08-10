<?php
/**
 * The Kronolith_View_Month:: class provides an API for viewing
 * months.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Month {

    /**
     * @var integer
     */
    var $month;

    /**
     * @var integer
     */
    var $year;

    /**
     * @var Horde_Date
     */
    var $date;

    /**
     * @var array
     */
    var $_events = array();

    /**
     * @var array
     */
    var $_currentCalendars = array();

    /**
     * @var integer
     */
    var $_daysInView;

    /**
     * @var integer
     */
    var $_startOfView;

    /**
     * @var integer
     */
    var $_startday;

    function Kronolith_View_Month($date)
    {
        global $prefs;

        $this->month = $date->month;
        $this->year = $date->year;

        // Need to calculate the start and length of the view.
        $this->date = new Horde_Date($date);
        $this->date->mday = 1;
        $this->_startday = $this->date->dayOfWeek();
        $this->_daysInView = Date_Calc::weeksInMonth($this->month, $this->year) * 7;
        if (!$prefs->getValue('week_start_monday')) {
            $this->_startOfView = 1 - $this->_startday;

            // We may need to adjust the number of days in the view if
            // we're starting weeks on Sunday.
            if ($this->_startday == Horde_Date::DATE_SUNDAY) {
                $this->_daysInView -= 7;
            }
            $endday = new Horde_Date(array('mday' => Horde_Date_Utils::daysInMonth($this->month, $this->year),
                                           'month' => $this->month,
                                           'year' => $this->year));
            $endday = $endday->dayOfWeek();
            if ($endday == Horde_Date::DATE_SUNDAY) {
                $this->_daysInView += 7;
            }
        } else {
            if ($this->_startday == Horde_Date::DATE_SUNDAY) {
                $this->_startOfView = -5;
            } else {
                $this->_startOfView = 2 - $this->_startday;
            }
        }

        $startDate = new Horde_Date(array('year' => $this->year,
                                          'month' => $this->month,
                                          'mday' => $this->_startOfView));
        $endDate = new Horde_Date(array('year' => $this->year,
                                        'month' => $this->month,
                                        'mday' => $this->_startOfView + $this->_daysInView));

        if ($prefs->getValue('show_shared_side_by_side')) {
            $allCalendars = Kronolith::listInternalCalendars();
            $this->_currentCalendars = array();
            foreach ($GLOBALS['display_calendars'] as $id) {
                $this->_currentCalendars[$id] = $allCalendars[$id];
            }
        } else {
            $this->_currentCalendars = array('internal_0' => true);
        }

        try {
            $this->_events = Kronolith::listEvents($startDate, $endDate);
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

        $sidebyside = $prefs->getValue('show_shared_side_by_side');
        $twentyFour = $prefs->getValue('twentyFour');
        $addLinks = Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
            (!empty($GLOBALS['conf']['hooks']['permsdenied']) ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents());

        if ($sidebyside) {
            require KRONOLITH_TEMPLATES . '/month/head_side_by_side.inc';
        } else {
            require KRONOLITH_TEMPLATES . '/month/head.inc';
        }

        $html = '';
        if (!$sidebyside && count($this->_currentCalendars)) {
            $html .= '<tr>';
        }

        $showLocation = Kronolith::viewShowLocation();
        $showTime = Kronolith::viewShowTime();
        $day_url = Horde::applicationUrl('day.php');
        $this_link = $this->link(0, true);
        $new_url = Horde::applicationUrl('new.php')->add('url', $this_link);
        $new_img = Horde::img('new_small.png', '+');

        foreach ($this->_currentCalendars as $id => $cal) {
            if ($sidebyside) {
                $html .= '<tr>';
            }

            $cell = 0;
            for ($day = $this->_startOfView; $day < $this->_startOfView + $this->_daysInView; ++$day) {
                $date = new Kronolith_Day($this->month, $day, $this->year);
                $date->hour = $twentyFour ? 12 : 6;
                $week = $date->weekOfYear();

                if ($cell % 7 == 0 && $cell != 0) {
                    if ($sidebyside) {
                        $html .= '<td>' . htmlspecialchars($cal->get('name')) . '</td>';
                    } else {
                        $html .= "</tr>\n<tr>";
                    }
                }
                if ($date->isToday()) {
                    $style = 'today';
                } elseif ($date->month != $this->month) {
                    $style = 'othermonth';
                } elseif ($date->dayOfWeek() == 0 || $date->dayOfWeek() == 6) {
                    $style = 'weekend';
                } else {
                    $style = 'text';
                }

                $html .= '<td class="' . $style . '" height="70" width="14%" valign="top"><div>';

                $html .= $day_url->add('date', $date->dateString())
                    ->link(array('class' => 'day'))
                    . $date->mday . '</a>';

                if ($addLinks) {
                    $new_url->add('date', $date->dateString());
                    if ($sidebyside) {
                        $new_url->add('calendar', $id);
                    }
                    $html .= $new_url->link(array('title' => _("Create a New Event"), 'class' => 'newEvent'))
                        . $new_img . '</a>';
                }

                if ($date->dayOfWeek() == Horde_Date::DATE_MONDAY) {
                    $html .= Horde::applicationUrl('week.php')
                        ->add('date', $date->dateString())
                        ->link(array('class' => 'week'))
                        . sprintf(_("Week %d"), $week) . '</a>';
                }

                $html .= '</div><div class="clear">&nbsp;</div>';

                $date_stamp = $date->dateString();
                if (!empty($this->_events[$date_stamp])) {
                    foreach ($this->_events[$date_stamp] as $event) {
                        if (!$sidebyside || $event->calendar == $id) {
                            $html .= '<div class="month-eventbox"' . $event->getCSSColors() . '>'
                                . $event->getLink($date, true, $this_link);
                            if ($showTime) {
                                $html .= '<div class="event-time">' . htmlspecialchars($event->getTimeRange()) . '</div>';
                            }
                            if ($showLocation) {
                                $html .= '<div class="event-location">' . htmlspecialchars($event->location) . '</div>';
                            }
                            $html .= '</div>';
                        }
                    }
                }

                $html .= "</td>\n";
                ++$cell;
            }

            if ($sidebyside) {
                $html .= '</tr>';
            }
        }
        if (!$sidebyside && count($this->_currentCalendars)) {
            $html .= '</tr>';
        }

        echo $html . '</tbody></table>';
    }

    function getMonth($offset = 0)
    {
        $month = new Horde_Date($this->date);
        $month->month += $offset;
        return $month;
    }

    function link($offset = 0, $full = false)
    {
        $month = $this->getMonth($offset);
        return Horde::applicationUrl('month.php', $full)
            ->add('date', $month->dateString());
    }

    function getName()
    {
        return 'Month';
    }

}
