<?php
/**
 * The Kronolith_View_Month:: class provides an API for viewing
 * months.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Month
{
    /**
     * @var integer
     */
    public $month;

    /**
     * @var integer
     */
    public $year;

    /**
     * @var Horde_Date
     */
    public $date;

    /**
     * @var array
     */
    protected $_events = array();

    /**
     * @var array
     */
    protected $_currentCalendars = array();

    /**
     * @var integer
     */
    protected $_startOfView;

    /**
     * @var integer
     */
    protected $_startday;

    /**
     *
     * @global Horde_Prefs $prefs
     * @param Horde_Date $date
     *
     * @return Kronolith_View_Month
     */
    public function __construct(Horde_Date $date)
    {
        global $prefs;

        $this->month = $date->month;
        $this->year = $date->year;

        // Need to calculate the start and length of the view.
        $this->date = new Horde_Date($date);
        $this->date->mday = 1;
        $this->_startday = $this->date->dayOfWeek();
        if (!$prefs->getValue('week_start_monday')) {
            $this->_startOfView = 1 - $this->_startday;
        } else {
            if ($this->_startday == Horde_Date::DATE_SUNDAY) {
                $this->_startOfView = -5;
            } else {
                $this->_startOfView = 2 - $this->_startday;
            }
        }

        $startDate = new Horde_Date(
            $this->year, $this->month, $this->_startOfView
        );
        $endDate = new Horde_Date(
            $this->year,
            $this->month,
            Horde_Date_Utils::daysInMonth($this->month, $this->year) + 1
        );
        $endDate->mday +=
            (7 - ($endDate->format('w') - $prefs->getValue('week_start_monday')))
            % 7;

        if ($prefs->getValue('show_shared_side_by_side')) {
            $allCalendars = Kronolith::listInternalCalendars();
            $this->_currentCalendars = array();
            foreach ($GLOBALS['calendar_manager']->get(Kronolith::DISPLAY_CALENDARS) as $id) {
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

    public function html()
    {
        global $prefs;

        $sidebyside = $prefs->getValue('show_shared_side_by_side');
        $twentyFour = $prefs->getValue('twentyFour');
        $addLinks = Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
            ($GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') > Kronolith::countEvents());

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
        $day_url = Horde::url('day.php');
        $this_link = $this->link(0, true);
        $new_url = Horde::url('new.php')->add('url', $this_link);
        $new_img = Horde::img('new_small.png', '+');
        $weekOffset = $prefs->getValue('week_start_monday') ? 0 : 1;
        $weekStart = $prefs->getValue('week_start_monday');

        foreach ($this->_currentCalendars as $id => $cal) {
            if ($sidebyside) {
                $html .= '<tr>';
            }

            $cell = 0;
            $date = new Kronolith_Day($this->month, $this->_startOfView, $this->year);
            $date->hour = $twentyFour ? 12 : 6;
            for (;
                 $date->month <= $this->month ||
                     ($date->month == 12 && $this->month == 1) ||
                     $date->format('w') != $weekStart;
                 $date->mday++) {
                if ($cell % 7 == 0) {
                    $week = $date->add(array('day' => $weekOffset))->weekOfYear();
                    $weeklink = Horde::url('week.php')
                        ->add('date', $date->dateString())
                        ->link(array('class' => 'kronolith-weeklink'))
                        . ($sidebyside ? sprintf(_("Week %d"), $week) : $week)
                        . '</a>';
                    if ($sidebyside) {
                        $html .= sprintf('<td class="kronolith-first-col">%s<br />%s</td>',
                                         $weeklink,
                                         htmlspecialchars(Kronolith::getLabel($cal)));
                    } else {
                        if ($cell != 0) {
                            $html .= "</tr>\n<tr>";
                        }
                        $html .= '<td class="kronolith-first-col">'
                            . $weeklink . '</td>';
                    }
                }
                if ($date->isToday()) {
                    $style = ' class="kronolith-today"';
                } elseif ($date->month != $this->month) {
                    $style = ' class="kronolith-other-month"';
                } elseif ($date->dayOfWeek() == 0 || $date->dayOfWeek() == 6) {
                    $style = ' class="kronolith-weekend"';
                } else {
                    $style = '';
                }

                $html .= '<td' . $style . '><div class="kronolith-day">';

                $html .= $day_url->add('date', $date->dateString())->link()
                    . $date->mday . '</a>';

                if ($addLinks) {
                    $new_url->add('date', $date->dateString());
                    if ($sidebyside) {
                        $new_url->add('calendar', $id);
                    }
                    $html .= $new_url->link(array('title' => _("Create a New Event"), 'class' => 'newEvent'))
                        . $new_img . '</a>';
                }

                $html .= '</div>';

                $date_stamp = $date->dateString();
                if (!empty($this->_events[$date_stamp])) {
                    foreach ($this->_events[$date_stamp] as $event) {
                        if (!$sidebyside || $event->calendar == $id) {
                            $html .= '<div class="kronolith-event"' . $event->getCSSColors() . '>';
                            if ($showTime && !$event->isAllDay()) {
                                $html .= '<span class="kronolith-time">' . htmlspecialchars($event->getTimeRange()) . '</span>';
                            }
                            $html .= $event->getLink($date, true, $this_link);
                            if (!$event->isPrivate() && $showLocation) {
                                $html .= '<span class="kronolith-location">' . htmlspecialchars($event->getLocation()) . '</span>';
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

    public function getMonth($offset = 0)
    {
        $month = new Horde_Date($this->date);
        $month->month += $offset;
        return $month;
    }

    public function link($offset = 0, $full = false)
    {
        $month = $this->getMonth($offset);
        return Horde::url('month.php', $full)
            ->add('date', $month->dateString());
    }

    public function getName()
    {
        return 'Month';
    }

}
