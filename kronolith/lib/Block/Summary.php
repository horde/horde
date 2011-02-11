<?php
/**
 * Block to display a summary of calendar items.
 */
class Kronolith_Block_Summary extends Horde_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        if (!isset($this->_params['days'])) {
            $this->_params['days'] = 7;
        }
    }

    /**
     */
    public function getName()
    {
        return _("Calendar Summary");
    }

    /**
     */
    protected function _params()
    {
        $params = array(
            'calendar' => array(
                'name' => _("Calendar"),
                'type' => 'enum',
                'default' => '__all'
            ),
            'days' => array(
                'name' => _("The time span to show"),
                'type' => 'enum',
                'default' => 7,
                'values' => array(
                    1 => '1 ' . _("day"),
                    2 => '2 ' . _("days"),
                    3 => '3 ' . _("days"),
                    4 => '4 ' . _("days"),
                    5 => '5 ' . _("days"),
                    6 => '6 ' . _("days"),
                    7 => '1 ' . _("week"),
                    14 => '2 ' . _("weeks"),
                    21 => '3 ' . _("weeks"),
                    28 => '4 ' . _("weeks")
                )
            ),
            'maxevents' => array(
                'name' => _("Maximum number of events to display (0 = no limit)"),
                'type' => 'int',
                'default' => 0
            ),
            'alarms' => array(
                'name' => _("Show only events that have an alarm set?"),
                'type' => 'checkbox',
                'default' => 0
            )
        );

        $params['calendar']['values']['__all'] = _("All Visible");
        foreach (Kronolith::listCalendars(Horde_Perms::SHOW, true) as $id => $cal) {
            $params['calendar']['values'][$id] = $cal->name();
        }

        return $params;
    }

    /**
     */
    protected function _title()
    {
        $url = Horde::url($GLOBALS['registry']->getInitialPage(), true);
        if (isset($this->_params['calendar']) &&
            $this->_params['calendar'] != '__all') {
            $url->add('display_cal', $this->_params['calendar']);
        }
        return $url->link()
            . htmlspecialchars($GLOBALS['registry']->get('name')) . '</a>';
    }

    /**
     */
    protected function _content()
    {
        Horde::addScriptFile('tooltips.js', 'horde');

        $now = new Horde_Date($_SERVER['REQUEST_TIME']);
        $today = date('j');

        $startDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j')));
        $endDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j') + $this->_params['days']));

        try {
            if (isset($this->_params['calendar']) &&
                $this->_params['calendar'] != '__all') {
                $calendars = Kronolith::listCalendars();
                if (!isset($calendars[$this->_params['calendar']])) {
                    return _("Calendar not found");
                }
                if (!$calendars[$this->_params['calendar']]->hasPermission(Horde_Perms::READ)) {
                    return _("Permission Denied");
                }
                list($type, $calendar) = explode('_', $this->_params['calendar'], 2);
                $driver = Kronolith::getDriver($type, $calendar);
                $all_events = $driver->listEvents($startDate, $endDate, true);
            } else {
                $all_events = Kronolith::listEvents($startDate, $endDate,
                                                    $GLOBALS['display_calendars']);
            }
        } catch (Exception $e) {
            return '<em>' . $e->getMessage() . '</em>';
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
                        $dayname = $day->strftime($GLOBALS['prefs']->getValue('date_format'));
                    }
                    $url = Horde::url('day.php', true)
                        ->setRaw(false)
                        ->add('date', $day->dateString());
                    if (isset($this->_params['calendar']) &&
                        $this->_params['calendar'] != '__all') {
                        $url->add('display_cal', $this->_params['calendar']);
                    }
                    $html .= $url->link(array('title' => sprintf(_("Goto %s"), $dayname)))
                        . $dayname . '</a></strong></td></tr>';
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
                    $time = $event->start->format($GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:ia')
                        . '-' . $event->end->format($GLOBALS['prefs']->getValue('twentyFour') ? 'H:i' : 'h:ia');
                }
                $html .= $time;
                if ($event_active) {
                    $html .= '</strong>';
                }
                $html .= '&nbsp;</td>';

                $html .= '<td class="text" valign="top">';
                if ($event_active) {
                    $html .= '<strong>';
                }
                $html .= ' ' . $event->getLink(null, true, null, true, true);
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
