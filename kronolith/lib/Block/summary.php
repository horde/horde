<?php

$block_name = _("Calendar Summary");

/**
 * Horde_Block_Kronolith_summary:: Implementation of the Horde_Block API to
 * display a summary of calendar items.
 *
 * @package Horde_Block
 */
class Horde_Block_Kronolith_summary extends Horde_Block {

    var $_app = 'kronolith';

    function Horde_Block_Kronolith_summary($params = array(), $row = null, $col = null)
    {
        parent::Horde_Block($params, $row, $col);
        if (!isset($this->_params['days'])) {
            $this->_params['days'] = 7;
        }
    }

    function _params()
    {
        @define('KRONOLITH_BASE', dirname(__FILE__) . '/../..');

        require_once KRONOLITH_BASE . '/lib/base.php';

        $params = array('calendar' => array('name' => _("Calendar"),
                                            'type' => 'enum',
                                            'default' => '__all'),
                        'days' => array('name' => _("The time span to show"),
                                        'type' => 'enum',
                                        'default' => 7,
                                        'values' => array(1 => '1 ' . _("day"),
                                                          2 => '2 ' . _("days"),
                                                          3 => '3 ' . _("days"),
                                                          4 => '4 ' . _("days"),
                                                          5 => '5 ' . _("days"),
                                                          6 => '6 ' . _("days"),
                                                          7 => '1 ' . _("week"),
                                                          14 => '2 ' . _("weeks"),
                                                          21 => '3 ' . _("weeks"),
                                                          28 => '4 ' . _("weeks"))),
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
        return Horde::link(Horde::url(Horde_Util::addParameter($registry->getInitialPage(), $url_params), true)) . htmlspecialchars($registry->get('name')) . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        global $registry, $prefs;

        // @TODO Remove this hack when maintenance is refactored.
        $no_maint = true;
        require_once dirname(__FILE__) . '/../base.php';

        Horde::addScriptFile('tooltips.js', 'horde');

        $now = new Horde_Date($_SERVER['REQUEST_TIME']);
        $today = date('j');

        $startDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j')));
        $endDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j') + $this->_params['days']));

        if (isset($this->_params['calendar']) &&
            $this->_params['calendar'] != '__all') {

            $calendar = $GLOBALS['kronolith_shares']->getShare($this->_params['calendar']);
            if (!is_a($calendar, 'PEAR_Error') && !$calendar->hasPermission(Horde_Auth::getAuth(), PERMS_SHOW)) {
                return _("Permission Denied");
            }

            $all_events = Kronolith::listEvents($startDate,
                                                $endDate,
                                                array($this->_params['calendar']),
                                                true, false, false);
        } else {
            $all_events = Kronolith::listEvents($startDate,
                                                $endDate,
                                                $GLOBALS['display_calendars']);
        }
        if (is_a($all_events, 'PEAR_Error')) {
            return '<em>' . $all_events->getMessage() . '</em>';
        }

        $html = '';
        $iMax = $today + $this->_params['days'];
        $firstday = true;
        $totalevents = 0;
        for ($i = $today; $i < $iMax; ++$i) {
            $day = new Kronolith_Day(date('n'), $i);
            $date_stamp = $day->dateString();
            if (empty($all_events[$date_stamp])) {
                continue;
            }

            $firstevent = true;
            $tomorrow = $day->getTomorrow();
            foreach ($all_events[$date_stamp] as $event) {
                if (!empty($this->_params['maxevents']) &&
                    $totalevents >= $this->_params['maxevents']) {
                    break 2;
                }

                if ($event->start->compareDate($day) < 0) {
                    $event->start = $day;
                }
                if ($event->end->compareDate($tomorrow) >= 0) {
                    $event->end = $tomorrow;
                }
                if ($event->end->compareDate($now) < 0) {
                    continue;
                }

                if (!empty($this->_params['alarms']) && !$event->alarm) {
                    continue;
                }
                $event_active = $event->start->compareDateTime($now) < 0 &&
                    $event->end->compareDateTime($now) > 0;

                if ($firstevent) {
                    if (!$firstday) {
                        $html .= '<tr><td colspan="3" style="line-height:2px">&nbsp;</td></tr>';
                    }
                    $html .= '<tr><td colspan="3" class="control"><strong>';
                    if ($day->isToday()) {
                        $dayname = _("Today");
                    } elseif ($day->isTomorrow()) {
                        $dayname = _("Tomorrow");
                    } elseif ($day->diff() < 7) {
                        $dayname = $day->strftime('%A');
                    } else {
                        $dayname = $day->strftime($prefs->getValue('date_format'));
                    }
                    $url_params = array('date' => $day->dateString());
                    if (isset($this->_params['calendar']) &&
                        $this->_params['calendar'] != '__all') {
                        $url_params['display_cal'] = $this->_params['calendar'];
                    }
                    $daylink = Horde::applicationUrl('day.php', true);
                    $daylink = Horde_Util::addParameter($daylink, $url_params);
                    $html .= Horde::link($daylink, sprintf(_("Goto %s"),
                                                           $dayname));
                    $html .= $dayname . '</a></strong></td></tr>';
                    $firstevent = false;
                    $firstday = false;
                }
                $html .= '<tr class="linedRow"><td class="text nowrap" valign="top">';
                if ($event_active) {
                    $html .= '<strong>';
                }

                if ($event->isAllDay()) {
                    $time = _("All day");
                } else {
                    $time = $event->start->format($prefs->getValue('twentyFour') ? 'H:i' : 'h:ia')
                        . '-' . $event->end->format($prefs->getValue('twentyFour') ? 'H:i' : 'h:ia');
                }

                $text = $event->getTitle();
                if ($location = $event->getLocation()) {
                    $text .= ' (' . $location . ')';
                }
                $html .= $time;
                if ($event_active) {
                    $html .= '</strong>';
                }

                if ($event_active) {
                    $html .= '<strong>';
                }
                $html .= ' ' . $event->getLink(null, true, null, true);
                if ($event_active) {
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
