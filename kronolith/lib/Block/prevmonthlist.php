<?php

$block_name = _("Prior Events");

/**
 * Horde_Block_Kronolith_monthlist:: Implementation of the Horde_Block
 * API to display a list of previous calendar items grouped by month.
 *
 * @package Horde_Block
 */
class Horde_Block_Kronolith_prevmonthlist extends Horde_Block
{
    protected $_app = 'kronolith';

    protected function _params()
    {
        $params = array('calendar' => array('name' => _("Calendar"),
                                            'type' => 'enum',
                                            'default' => '__all'),
                        'months' => array('name' => _("Months Before"),
                                          'type' => 'int',
                                          'default' => 2),
                        'alarms' => array('name' => _("Show only events that have an alarm set?"),
                                          'type' => 'checkbox',
                                          'default' => 0));
        $params['calendar']['values']['__all'] = _("All Visible");
        foreach (Kronolith::listCalendars(Horde_Perms::SHOW, true) as $id => $cal) {
            $params['calendar']['values'][$id] = $cal->name();
        }

        return $params;
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    protected function _title()
    {
        $url = Horde::url($GLOBALS['registry']->getInitialPage(), true);
        if (isset($this->_params['calendar']) &&
            $this->_params['calendar'] != '__all') {
            $url->add('display_cal', $this->_params['calendar']);
        }

        return $url->link() . _("Prior Events") . '</a>';
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    protected function _content()
    {
        global $registry, $prefs;

        $GLOBALS['from_block'] = true;

        Horde::addScriptFile('tooltips.js', 'horde');

        $startDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n') - $this->_params['months'], 'mday' => date('j')));
        $endDate = new Horde_Date(array('year' => date('Y'), 'month' => date('n'), 'mday' => date('j') - 1));

        $current_month = '';

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
                list($type, $calendar) = explode('_', $this->_params['calendar']                $driver = Kronolith::getDriver($type, $calendar);
                $all_events = $driver->listEvents($startDate, $endDate, true);
            } else {
                $all_events = Kronolith::listEvents($startDate, $endDate, $GLOBALS['display_calendars']);
            }
        } catch (Exception $e) {
            return '<em>' . $e->getMessage() . '</em>';
        }

        $html = '';

        /* How many days do we need to check. */
        $days = Date_Calc::dateDiff($startDate->mday, $startDate->month, $startDate->year,
                                    $endDate->mday, $endDate->month, $endDate->year);

        /* Loop through the days. */
        for ($i = 0; $i < $days; ++$i) {
            $day = new Kronolith_Day($startDate->month, $startDate->mday + $i, $startDate->year);
            if (empty($all_events[$day->dateString()])) {
                continue;
            }

            /* Output month header. */
            if ($current_month != $day->month) {
                $current_month = $day->strftime('%m');
                $html .= '<tr><td colspan="4" class="control"><strong>' . $day->strftime('%B') . '</strong></td></tr>';
            }

            $firstevent = true;
            $tomorrow = $day->getTomorrow();
            foreach ($all_events[$day->dateString()] as $event) {

                if ($event->start->compareDate($day) < 0) {
                    $event->start = new Horde_Date($day);
                }
                if ($event->end->compareDate($tomorrow) >= 0) {
                    $event->end = $tomorrow;
                }

                if ($event->end->compareDate($startDate) < 0) {
                    continue;
                }

                if ($this->_params['alarms'] && !$event->alarm) {
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
                if ($event->start->compareDate($startDate) < 0 &&
                    $event->end->compareDate($startDate) > 0) {
                    $html .= '<strong>' . $event->location . '</strong>';
                } else {
                    $html .= $event->location;
                }
                if ($event->start->compareDate($startDate) < 0 &&
                    $event->end->compareDate($startDate) > 0) {
                    $html .= '<strong>';
                }
                $html .= $event->getLink(null, true, null, true);
                if ($event->start->compareDate($startDate) < 0 &&
                    $event->end->compareDate($startDate) > 0) {
                    $html .= '</strong>';
                }
                $html .= '</td></tr>';
            }
        }

        if (empty($html)) {
            return '<em>' . _("No events to display") . '</em>';
        }

        return '<table cellspacing="0" width="100%">' . $html . '</table>';
    }

}
