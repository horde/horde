<?php
/**
 * The Kronolith_View_Day:: class provides an API for viewing days.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Kronolith
 */
class Kronolith_View_Day extends Kronolith_Day
{
    public $all_day_events = array();
    public $all_day_rowspan = array();
    public $all_day_maxrowspan = 0;
    public $span = array();
    public $totalspan = 0;
    public $sidebyside = false;
    protected $_events = array();
    protected $_event_matrix = array();
    protected $_parsed = false;
    protected $_currentCalendars = array();
    protected $_first;
    protected $_last;

    /**
     *
     * @param Horde_Date $date  The day for this view
     * @param array $events     An array of Kronolith_Event objects
     *
     * @return Kronolith_View_Day
     */
    public function __construct(Horde_Date $date, array $events = null)
    {
        parent::__construct($date->month, $date->mday, $date->year);

        $this->sidebyside = $GLOBALS['prefs']->getValue('show_shared_side_by_side');
        if ($this->sidebyside) {
            $allCalendars = Kronolith::listInternalCalendars();
            foreach ($GLOBALS['display_calendars'] as $cid) {
                 $this->_currentCalendars[$cid] = $allCalendars[$cid];
                 $this->all_day_events[$cid] = array();
            }
        } else {
            $this->_currentCalendars = array(0);
        }

        if ($events === null) {
            try {
                $events = Kronolith::listEvents(
                    $this,
                    new Horde_Date(array('year' => $this->year,
                                         'month' => $this->month,
                                         'mday' => $this->mday)));
                $this->_events = array_shift($events);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                $this->_events = array();
            }
        } else {
            $this->_events = $events;
        }

        if (!is_array($this->_events)) {
            $this->_events = array();
        }
    }

    /**
     * Setter for events array
     *
     * @param array $events
     */
    public function setEvents(array $events)
    {
        $this->_events = $events;
    }

    public function html()
    {
        global $prefs;

        if (!$this->_parsed) {
            $this->parse();
        }

        $started = false;
        $first_row = true;
        $addLinks = Kronolith::getDefaultCalendar(Horde_Perms::EDIT) &&
            ($GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Perms')->hasAppPermission('max_events') > Kronolith::countEvents());
        $showLocation = Kronolith::viewShowLocation();
        $showTime = Kronolith::viewShowTime();

        require KRONOLITH_TEMPLATES . '/day/head.inc';
        if ($this->sidebyside) {
            require KRONOLITH_TEMPLATES . '/day/head_side_by_side.inc';
        }
        echo '<tbody>';

        if ($addLinks) {
            $newEventUrl = Horde::url('new.php')
                ->add(array('datetime' => sprintf($this->dateString() . '%02d%02d00',
                                                  $this->slots[0]['hour'], $this->slots[0]['min']),
                            'allday' => 1,
                            'url' => $this->link(0, true)))
                ->link(array('title' => _("Create a New Event"), 'class' =>
            'hour'))
                . _("All day")
                . Horde::img('new_small.png', '+', array('class' =>
            'iconAdd'))
                . '</a>';
        } else {
            $newEventUrl = '<span class="hour">' . _("All day") . '</span>';
        }

        /* The all day events are not listed in different columns, but in
         * different rows.  In side by side view we do not spread an event
         * over multiple rows if there are different numbers of all day events
         * for different calendars.  We just put one event in a single row
         * with no rowspan.  We put in a rowspan in the row after the last
         * event to fill all remaining rows. */
        $row = '';
        $rowspan = ($this->all_day_maxrowspan) ? ' rowspan="' . $this->all_day_maxrowspan . '"' : '';
        for ($k = 0; $k < $this->all_day_maxrowspan; ++$k) {
            $row = '';
            foreach (array_keys($this->_currentCalendars) as $cid) {
                if (count($this->all_day_events[$cid]) === $k) {
                    // There are no events or all events for this calendar
                    // have already been printed.
                    $row .= '<td class="allday" width="1%" rowspan="' . ($this->all_day_maxrowspan - $k) . '" colspan="'.  $this->span[$cid] . '">&nbsp;</td>';
                } elseif (count($this->all_day_events[$cid]) > $k) {
                    // We have not printed every all day event yet. Put one
                    // into this row.
                    $event = $this->all_day_events[$cid][$k];
                    $row .= '<td class="day-eventbox"'
                        . $event->getCSSColors()
                        . 'width="' . round(90 / count($this->_currentCalendars))  . '%" '
                        . 'valign="top" colspan="' . $this->span[$cid] . '">'
                        . $event->getLink($this, true, $this->link(0, true));
                    if ($showLocation) {
                        $row .= '<div class="event-location">' . htmlspecialchars($event->location) . '</div>';
                    }
                    $row .= '</td>';
                }
            }
            require KRONOLITH_TEMPLATES . '/day/all_day.inc';
            $first_row = false;
        }

        if ($first_row) {
            $row .= '<td colspan="' . $this->totalspan . '" width="100%">&nbsp;</td>';
            require KRONOLITH_TEMPLATES . '/day/all_day.inc';
        }

        $day_hour_force = $prefs->getValue('day_hour_force');
        $day_hour_start = $prefs->getValue('day_hour_start') / 2 * $this->slotsPerHour;
        $day_hour_end = $prefs->getValue('day_hour_end') / 2 * $this->slotsPerHour;
        $rows = array();
        $covered = array();

        for ($i = 0; $i < $this->slotsPerDay; ++$i) {
            if ($i >= $day_hour_end && $i > $this->_last) {
                break;
            }
            if ($i < $this->_first && $i < $day_hour_start) {
                continue;
            }

            $row = '';
            if (($m = $i % $this->slotsPerHour) != 0) {
                $time = ':' . $m * $this->slotLength;
                $hourclass = 'halfhour';
            } else {
                $time = Kronolith_View_Day::prefHourFormat($this->slots[$i]['hour']);
                $hourclass = 'hour';
            }

            if (!count($this->_currentCalendars)) {
                $row .= '<td>&nbsp;</td>';
            }

            foreach (array_keys($this->_currentCalendars) as $cid) {
                $hspan = 0;
                foreach ($this->_event_matrix[$cid][$i] as $key) {
                    $event = &$this->_events[$key];

                    // Since we've made sure that this event's overlap is a
                    // factor of the total span, we get this event's
                    // individual span by dividing the total span by this
                    // event's overlap.
                    $span = $this->span[$cid] / $event->overlap;

                    // Store the indent we're starting this event at
                    // for future use.
                    if (!isset($event->indent)) {
                        $event->indent = $hspan;
                    }

                    // If the first node that we would cover is
                    // already covered, we can assume that table
                    // rendering will take care of pushing the event
                    // over. However, if the first node _isn't_
                    // covered but any others that we would cover
                    // _are_, we only cover the available nodes.
                    if (!isset($covered[$i][$event->indent])) {
                        $collision = false;
                        $available = 0;
                        for ($y = $event->indent; $y < ($span + $event->indent); ++$y) {
                            if (isset($covered[$i][$y])) {
                                $collision = true;
                                break;
                            }
                            $available++;
                        }

                        if ($collision) {
                            $span = $available;
                        }
                    }

                    $hspan += $span;

                    $start = new Horde_Date(array(
                        'hour'  => floor($i / $this->slotsPerHour),
                        'min'   => ($i % $this->slotsPerHour) * $this->slotLength,
                        'month' => $this->month,
                        'mday'  => $this->mday,
                        'year'  => $this->year));
                    $end_slot = new Horde_Date($start);
                    $end_slot->min += $this->slotLength;
                    if (((!$day_hour_force || $i >= $day_hour_start) &&
                         $event->start->compareDateTime($start) >= 0 &&
                         $event->start->compareDateTime($end_slot) < 0 ||
                         $start->compareDateTime($this) == 0) ||
                        ($day_hour_force &&
                         $i == $day_hour_start &&
                         $event->start->compareDateTime($start) < 0)) {

                        // Store the nodes that we're covering for
                        // this event in the coverage graph.
                        for ($x = $i; $x < ($i + $event->rowspan); ++$x) {
                            for ($y = $event->indent; $y < $hspan; ++$y) {
                                $covered[$x][$y] = true;
                            }
                        }

                        $row .= '<td class="day-eventbox"'
                            . $event->getCSSColors()
                            . 'width="' . round((90 / count($this->_currentCalendars)) * ($span / $this->span[$cid]))  . '%" '
                            . 'valign="top" colspan="' . $span . '" rowspan="' . $event->rowspan . '">'
                            . $event->getLink($this, true, $this->link(0, true));
                        if ($showTime) {
                            $row .= '<div class="event-time">' . htmlspecialchars($event->getTimeRange()) . '</div>';
                        }
                        if ($showLocation) {
                            $row .= '<div class="event-location">' . htmlspecialchars($event->location) . '</div>';
                        }
                        $row .= '</td>';
                    }
                }

                $diff = $this->span[$cid] - $hspan;
                if ($diff > 0) {
                    $row .= str_repeat('<td>&nbsp;</td>', $diff);
                }
            }

            if ($addLinks) {
                $newEventUrl = Horde::url('new.php')
                    ->add(array('datetime' => sprintf($this->dateString() . '%02d%02d00',
                                                      $this->slots[$i]['hour'], $this->slots[$i]['min']),
                                'url' => $this->link(0, true)))
                    ->link(array('title' =>_("Create a New Event"), 'class' => $hourclass))
                    . $time
                    . Horde::img('new_small.png', '+', array('class' => 'iconAdd'))
                    . '</a>';
            } else {
                $newEventUrl = '<span class="' . $hourclass . '">' . $time . '</span>';
            }

            $rows[] = array('row' => $row, 'slot' => $newEventUrl);
        }

        $template = $GLOBALS['injector']->createInstance('Horde_Template');
        $template->set('row_height', round(20 / $this->slotsPerHour));
        $template->set('rows', $rows);
        $template->set('show_slots', true, true);
        echo $template->fetch(KRONOLITH_TEMPLATES . '/day/rows.html')
            . '</tbody></table>';
    }

    /**
     * This function runs through the events and tries to figure out
     * what should be on each line of the output table. This is a
     * little tricky.
     */
    public function parse()
    {
        global $prefs;

        $tmp = array();
        $this->all_day_maxrowspan = 0;
        $day_hour_force = $prefs->getValue('day_hour_force');
        $day_hour_start = $prefs->getValue('day_hour_start') / 2 * $this->slotsPerHour;
        $day_hour_end = $prefs->getValue('day_hour_end') / 2 * $this->slotsPerHour;

        // Separate out all day events and do some initialization/prep
        // for parsing.
        foreach (array_keys($this->_currentCalendars) as $cid) {
            $this->all_day_events[$cid] = array();
            $this->all_day_rowspan[$cid] = 0;
        }

        foreach ($this->_events as $key => $event) {
            // If we have side_by_side we only want to include the
            // event in the proper calendar.
            if ($this->sidebyside) {
                $cid = $event->calendar;
            } else {
                $cid = 0;
            }

            // All day events are easy; store them seperately.
            if ($event->isAllDay()) {
                $this->all_day_events[$cid][] = clone $event;
                ++$this->all_day_rowspan[$cid];
                $this->all_day_maxrowspan = max($this->all_day_maxrowspan, $this->all_day_rowspan[$cid]);
            } else {
                // Initialize the number of events that this event
                // overlaps with.
                $event->overlap = 0;

                // Initialize this event's vertical span.
                $event->rowspan = 0;

                $tmp[] = clone $event;
            }
        }
        $this->_events = $tmp;

        // Initialize the set of different rowspans needed.
        $spans = array(1 => true);

        // Track the first and last slots in which we have an event
        // (they each start at the other end of the day and move
        // towards/past each other as we find events).
        $this->_first = $this->slotsPerDay;
        $this->_last = 0;

        // Run through every slot, adding in entries for every event
        // that we have here.
        for ($i = 0; $i < $this->slotsPerDay; ++$i) {
            // Initialize this slot in the event matrix.
            foreach (array_keys($this->_currentCalendars) as $cid) {
                $this->_event_matrix[$cid][$i] = array();
            }

            // Calculate the start and end times for this slot.
            $start = new Horde_Date(array(
                'hour'  => floor($i / $this->slotsPerHour),
                'min'   => ($i % $this->slotsPerHour) * $this->slotLength,
                'month' => $this->month,
                'mday'  => $this->mday,
                'year'  => $this->year));
            $end = clone $start;
            $end->min += $this->slotLength;

            // Search through our events.
            foreach ($this->_events as $key => $event) {
                // If we have side_by_side we only want to include the
                // event in the proper calendar.
                if ($this->sidebyside) {
                    $cid = $event->calendar;
                } else {
                    $cid = 0;
                }

                // If the event falls anywhere inside this slot, add
                // it, make sure other events know that they overlap
                // it, and increment the event's vertical span.
                if (($event->end->compareDateTime($start) > 0 &&
                     $event->start->compareDateTime($end) < 0) ||
                    ($event->end->compareDateTime($event->start) == 0 &&
                     $event->start->compareDateTime($start) == 0)) {

                    // Make sure we keep the latest hour that an event
                    // reaches up-to-date.
                    if ($i > $this->_last &&
                        (!$day_hour_force || $i <= $day_hour_end)) {
                        $this->_last = $i;
                    }

                    // Make sure we keep the first hour that an event
                    // reaches up-to-date.
                    if ($i < $this->_first &&
                        (!$day_hour_force || $i >= $day_hour_start)) {
                        $this->_first = $i;
                    }

                    if (!$day_hour_force ||
                        ($i >= $day_hour_start && $i <= $day_hour_end)) {
                        // Add this event to the events which are in this row.
                        $this->_event_matrix[$cid][$i][] = $key;

                        // Increment the event's vertical span.
                        ++$this->_events[$key]->rowspan;
                    }
                }
            }

            foreach (array_keys($this->_currentCalendars) as $cid) {
                // Update the number of events that events in this row
                // overlap with.
                $max = 0;
                $count = count($this->_event_matrix[$cid][$i]);
                foreach ($this->_event_matrix[$cid][$i] as $ev) {
                    $this->_events[$ev]->overlap = max($this->_events[$ev]->overlap, $count);
                    $max = max($max, $this->_events[$ev]->overlap);
                }

                // Update the set of rowspans to include the value for
                // this row.
                $spans[$cid][$max] = true;
            }
        }

        foreach (array_keys($this->_currentCalendars) as $cid) {
            // Sort every row by start time so that events always show
            // up here in the same order.
            for ($i = $this->_first; $i <= $this->_last; ++$i) {
                if (count($this->_event_matrix[$cid][$i])) {
                    usort($this->_event_matrix[$cid][$i], array($this, '_sortByStart'));
                }
            }

            // Now that we have the number of events in each row, we
            // can calculate the total span needed.
            $span[$cid] = 1;

            // Turn keys into array values.
            $spans[$cid] = array_keys($spans[$cid]);

            // Start with the biggest one first.
            rsort($spans[$cid]);
            foreach ($spans[$cid] as $s) {
                // If the number of events in this row doesn't divide
                // cleanly into the current total span, we need to
                // multiply the total span by the number of events in
                // this row.
                if ($s != 0 && $span[$cid] % $s != 0) {
                    $span[$cid] *= $s;
                }
            }
            $this->totalspan += $span[$cid];
        }
        // Set the final span.
        if (isset($span)) {
            $this->span = $span;
        } else {
            $this->totalspan = 1;
        }

        // We're now parsed and ready to go.
        $this->_parsed = true;
    }

    public function link($offset = 0, $full = false)
    {
        return Horde::url('day.php', $full)
            ->add('date', $this->getTime('%Y%m%d', $offset));
    }

    public function getName()
    {
        return 'Day';
    }

    public function prefHourFormat($hour)
    {
        $hour = $hour % 24;
        if ($GLOBALS['prefs']->getValue('twentyFour')) {
            return $hour;
        }
        return ($hour % 12 == 0 ? 12 : $hour % 12)
            . ($hour < 12 ? 'am' : 'pm');
    }

    protected function _sortByStart($evA, $evB)
    {
        $sA = $this->_events[$evA]->start;
        $sB = $this->_events[$evB]->start;

        return $sB->compareTime($sA);
    }

}
