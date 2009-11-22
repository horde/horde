<?php

$block_name = _("Upcoming Events");

/**
 * Horde_Block_Kronolith_monthlist:: Implementation of the Horde_Block API
 * to display a list of calendar items grouped by month.
 *
 * @package Horde_Block
 */
class Horde_Block_Kronolith_monthlist extends Horde_Block {

    var $_app = 'kronolith';

    function _params()
    {
        require_once dirname(__FILE__) . '/../base.php';

        $params = array('calendar' => array('name' => _("Calendar"),
                                            'type' => 'enum',
                                            'default' => '__all'),
                        'months'   => array('name' => _("Months Ahead"),
                                            'type' => 'int',
                                            'default' => 2),
                        'maxevents' => array('name' => _("Maximum number of events to display (0 = no limit)"),
                                             'type' => 'int',
                                             'default' => 0),
                        'alarms' => array('name' => _("Show only events that have an alarm set?"),
                                          'type' => 'checkbox',
                                          'default' => 0));
        $params['calendar']['values']['__all'] = _("All Visible");
        foreach (Kronolith::listCalendars() as $id => $cal) {
            $params['calendar']['values'][$id] = $cal->get('name');
        }

        return $params;
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        global $registry;
        if (isset($this->_params['calendar']) && $this->_params['calendar'] != '__all') {
            $url_params = array('display_cal' => $this->_params['calendar']);
        } else {
            $url_params = array();
        }
        return Horde::link(Horde::url(Horde_Util::addParameter($registry->getInitialPage(), $url_params), true)) . _("Upcoming Events") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        // @TODO Remove this hack when maintenance is refactored.
        $no_maint = true;
        require_once dirname(__FILE__) . '/../base.php';

        global $registry, $prefs;

        Horde::addScriptFile('tooltips.js', 'horde');

        $now = new Horde_Date($_SERVER['REQUEST_TIME']);
        $today = date('j');
        $current_month = '';

        $startDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j')));
        $endDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n') + $this->_params['months'], 'mday' => date('j') - 1));

        if (isset($this->_params['calendar']) && $this->_params['calendar'] != '__all') {
            $calendar = $GLOBALS['kronolith_shares']->getShare($this->_params['calendar']);
            if (!is_a($calendar, 'PEAR_Error') && !$calendar->hasPermission(Horde_Auth::getAuth(), Horde_Perms::SHOW)) {
                return _("Permission Denied");
            }
            $all_events = Kronolith::listEvents($startDate, $endDate, array($this->_params['calendar']), true, false, false);
        } else {
            $all_events = Kronolith::listEvents($startDate, $endDate, $GLOBALS['display_calendars']);
        }
        if (is_a($all_events, 'PEAR_Error')) {
            return '<em>' . $all_events->getMessage() . '</em>';
        }

        /* How many days do we need to check. */
        $days = Date_Calc::dateDiff($startDate->mday, $startDate->month, $startDate->year,
                                    $endDate->mday, $endDate->month, $endDate->year);

        /* Loop through the days. */
        $totalevents = 0;

        $html = '';
        for ($i = 0; $i < $days; ++$i) {
            $day = new Kronolith_Day($startDate->month, $today + $i);
            $date_stamp = $day->dateString();
            if (empty($all_events[$date_stamp])) {
                continue;
            }

            if (!empty($this->_params['maxevents']) &&
                $totalevents >= $this->_params['maxevents']) {
                break;
            }

            /* Output month header. */
            if ($current_month != $day->month) {
                $current_month = $day->strftime('%m');
                $html .= '<tr><td colspan="4" class="control"><strong>' . $day->strftime('%B') . '</strong></td></tr>';
            }

            $firstevent = true;
            $tomorrow = $day->getTomorrow();
            foreach ($all_events[$date_stamp] as $event) {
                if ($event->start->compareDate($day) < 0) {
                    $event->start = new Horde_Date($day);
                }
                if ($event->end->compareDate($tomorrow) >= 0) {
                    $event->end = $tomorrow;
                }
                if (($event->end->compareDate($now) < 0 && !$event->isAllDay()) ||
                    (!empty($this->_params['alarms']) && !$event->alarm)) {
                    continue;
                }

                if ($firstevent) {
                    $html .= '<tr><td class="text" valign="top" align="right"><strong>';
                    if ($day->isToday()) {
                        $html .= _("Today");
                    } elseif ($day->isTomorrow()) {
                        $html .= _("Tomorrow");
                    } else {
                        $html .= $day->mday;
                    }
                    $html .= '</strong>&nbsp;</td>';
                    $firstevent = false;
                } else {
                    $html .= '<tr><td class="text">&nbsp;</td>';
                }

                $html .= '<td class="text" nowrap="nowrap" valign="top">';
                if ($event->start->compareDate($now) < 0 &&
                    $event->end->compareDate($now) > 0) {
                    $html .= '<strong>' . $event->getLocation() . '</strong>';
                } else {
                    $html .= $event->getLocation();
                }

                if ($event->start->compareDate($now) < 0 &&
                    $event->end->compareDate($now) > 0) {
                    $html .= '<strong>';
                }
                if (isset($event->eventID)) {
                    $html .= $event->getLink(null, true, null, true);
                } elseif (isset($this->external)) {
                    $html .= Horde::link(Horde::url($registry->link($this->external . '/show', $event->external_params),
                                                    true), $event->getTitle()) . $event->getTitle() . '</a>';
                } else {
                    $html .= $event->getTitle();
                }
                if ($event->start->compareDate($now) < 0 &&
                    $event->end->compareDate($now) > 0) {
                    $html .= '</strong>';
                }
                $html .= '</td></tr>';

                $totalevents++;
            }
        }

        if (empty($html)) {
            return '<em>' . _("No events to display") . '</em>';
        }

        return '<table cellspacing="0" width="100%">' . $html . '</table>';
    }

}
