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
    public $span = array();
    public $totalspan = 0;
    public $sidebyside = false;
    public $events = array();
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
                $this->events = array_shift($events);
            } catch (Exception $e) {
                $GLOBALS['notification']->push($e, 'horde.error');
                $this->events = array();
            }
        } else {
            $this->events = $events;
        }

        if (!is_array($this->events)) {
            $this->events = array();
        }
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
            ($GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') === true ||
             $GLOBALS['injector']->getInstance('Horde_Core_Perms')->hasAppPermission('max_events') > Kronolith::countEvents());
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
                ->link(array('title' => _("Create a New Event")))
                . _("All day")
                . '</a>';
        } else {
            $newEventUrl = _("All day");
        }

        $row = '<td colspan="' . $this->totalspan . '">';
        foreach (array_keys($this->_currentCalendars) as $cid) {
            foreach ($this->all_day_events[$cid] as $event) {
                $row .= '<div class="kronolith-event"'
                    . $event->getCSSColors() . '>'
                    . $event->getLink($this, true, $this->link(0, true));
                if (!$event->isPrivate() && $showLocation) {
                    $row .= '<span class="event-location">'
                        . htmlspecialchars($event->getLocation()) . '</span>';
                }
                $row .= '</div>';
            }
        }
        $row .= '</td>';

        require KRONOLITH_TEMPLATES . '/day/all_day.inc';

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
            if (!count($this->_currentCalendars)) {
                $row .= '<td>&nbsp;</td>';
            }

            foreach (array_keys($this->_currentCalendars) as $cid) {
                $hspan = 0;
                foreach ($this->_event_matrix[$cid][$i] as $key) {
                    $event = &$this->events[$key];

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

                        $row .= '<td class="kronolith-event"'
                            . $event->getCSSColors()
                            . 'width="' . round((90 / count($this->_currentCalendars)) * ($span / $this->span[$cid]))  . '%" '
                            . 'valign="top" colspan="' . $span . '" rowspan="' . $event->rowspan . '">'
                            . '<div class="kronolith-event-info">';
                        if ($showTime) {
                            $row .= '<span class="kronolith-time">' . htmlspecialchars($event->getTimeRange()) . '</span>';
                        }
                        $row .= $event->getLink($this, true, $this->link(0, true));
                        if (!$event->isPrivate() && $showLocation) {
                            $row .= '<span class="kronolith-location">' . htmlspecialchars($event->getLocation()) . '</span>';
                        }
                        $row .= '</div></td>';
                    }
                }

                $diff = $this->span[$cid] - $hspan;
                if ($diff > 0) {
                    $row .= '<td colspan="' . $diff . '">&nbsp;</td>';
                }
            }

            $time = $this->prefHourFormat(
                $this->slots[$i]['hour'],
                ($i % $this->slotsPerHour) * $this->slotLength);
            if ($addLinks) {
                $newEventUrl = Horde::url('new.php')
                    ->add(array('datetime' => sprintf($this->dateString() . '%02d%02d00',
                                                      $this->slots[$i]['hour'], $this->slots[$i]['min']),
                                'url' => $this->link(0, true)))
                    ->link(array('title' =>_("Create a New Event")))
                    . $time
                    . '</a>';
            } else {
                $newEventUrl = $time;
            }

            $rows[] = array('row' => $row, 'slot' => $newEventUrl);
        }

        $template = $GLOBALS['injector']->createInstance('Horde_Template');
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
        $day_hour_force = $prefs->getValue('day_hour_force');
        $day_hour_start = $prefs->getValue('day_hour_start') / 2 * $this->slotsPerHour;
        $day_hour_end = $prefs->getValue('day_hour_end') / 2 * $this->slotsPerHour;

        // Separate out all day events and do some initialization/prep
        // for parsing.
        foreach (array_keys($this->_currentCalendars) as $cid) {
            $this->all_day_events[$cid] = array();
        }

        foreach ($this->events as $key => $event) {
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
            } else {
                // Initialize the number of events that this event
                // overlaps with.
                $event->overlap = 0;

                // Initialize this event's vertical span.
                $event->rowspan = 0;

                $tmp[] = clone $event;
            }
        }
        $this->events = $tmp;

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
            foreach ($this->events as $key => $event) {
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
                        ++$this->events[$key]->rowspan;
                    }
                }
            }

            foreach (array_keys($this->_currentCalendars) as $cid) {
                // Update the number of events that events in this row
                // overlap with.
                $max = 0;
                $count = count($this->_event_matrix[$cid][$i]);
                foreach ($this->_event_matrix[$cid][$i] as $ev) {
                    $this->events[$ev]->overlap = max($this->events[$ev]->overlap, $count);
                    $max = max($max, $this->events[$ev]->overlap);
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

    public function prefHourFormat($hour, $min)
    {
        $hour = ($hour % 24) . ':' . sprintf('%02d', $min);
        if ($GLOBALS['prefs']->getValue('twentyFour')) {
            return $hour;
        }
        return ($hour % 12 == 0 ? 12 : $hour % 12)
            . ($hour < 12 ? 'am' : 'pm');
    }

    protected function _sortByStart($evA, $evB)
    {
        $sA = $this->events[$evA]->start;
        $sB = $this->events[$evB]->start;

        return $sB->compareTime($sA);
    }

}
