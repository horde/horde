<?php
/**
 * A reduced event representation derived from the Kronolith event
 * representation.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * A reduced event representation derived from the Kronolith event
 * representation.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Object_Event {

    /** Event status */
    const STATUS_NONE        = 'none';
    const STATUS_UNKNOWN     = 'unknown';
    const STATUS_BUSY        = 'busy';
    const STATUS_TENTATIVE   = 'tentative';
    const STATUS_OUTOFOFFICE = 'outofoffice';
    const STATUS_FREE        = 'free';
    const STATUS_CONFIRMED   = 'confirmed';
    const STATUS_CANCELLED   = 'cancelled';

    /**
     * The driver unique identifier for this event.
     *
     * @var string
     */
    private $_event_id;

    /**
     * The start time of the event.
     *
     * @var Horde_Date
     */
    private $_start;

    /**
     * The end time of the event.
     *
     * @var Horde_Date
     */
    private $_end;

    /**
     * The title of this event.
     *
     * @var string
     */
    private $_title = '';

    /**
     * The location this event occurs at.
     *
     * @var string
     */
    private $_location = '';

    /**
     * Whether the event is private.
     *
     * @var boolean
     */
    private $_private = false;

    /**
     * Recurrence information.
     *
     * @var Horde_Date_Recurrence
     */
    private $_recurrence;

    /**
     * Constructor.
     *
     * @param array $event The event data.
     */
    public function __construct(array $event)
    {
        if (isset($event['uid'])) {
            $this->_event_id = $event['uid'];
        }

        if (!($event['start-date'] instanceOf Horde_Date)) {
            $this->_start = new Horde_Date($event['start-date']);
        } else {
            $this->_start = $event['start-date'];
        }

        if (!($event['end-date'] instanceOf Horde_Date)) {
            $this->_end = new Horde_Date($event['end-date']);
        } else {
            $this->_end = $event['end-date'];
        }

        if (isset($event['summary'])) {
            $this->_title = $event['summary'];
        }

        if (isset($event['location'])) {
            $this->_location = $event['location'];
        }

        if (isset($event['sensitivity']) && 
            ($event['sensitivity'] == 'private' ||
             $event['sensitivity'] == 'confidential')) {
            $this->_private = true;
        }

        if (isset($event['show-time-as'])) {
            $this->_status = $event['show-time-as'];
        } else {
            $this->_status = self::STATUS_NONE;
        }

        // Recurrence
        if (isset($event['recurrence'])) {
            $this->_recurrence = new Horde_Date_Recurrence($this->_start);
            $this->_recurrence->fromHash($event['recurrence']);
        }
    }

    /**
     * Determines if the event recurs in the given time span.
     *
     * @param Horde_Date $startDate Start of the time span.
     * @param Horde_Date $endDate   End of the time span.
     *
     * @return boolean True if the event recurs in this time span.
     */
    public function recursIn(Horde_Date $startDate, Horde_Date $endDate)
    {
        $next = $this->_recurrence->nextRecurrence($startDate);
        while ($next !== false &&
               $this->_recurrence->hasException($next->year, $next->month, $next->mday)) {
            $next->mday++;
            $next = $this->_recurrence->nextRecurrence($next);
        }

        if ($next !== false) {
            $duration = $next->timestamp() - $this->_start->timestamp();
            $next_end = new Horde_Date($this->_end->timestamp() + $duration);

            if ((!(($endDate->compareDateTime($next) < 0) ||
                   ($startDate->compareDateTime($next_end) > 0)))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether this event is a recurring event.
     *
     * @return boolean True if this is a recurring event.
     */
    public function recurs()
    {
        return isset($this->_recurrence) &&
            !$this->_recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_NONE);
    }

    /**
     * Return the event status (one of the event status codes defined in this
     * class).
     *
     * @return string The status of this event.
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Return the duration of the event.
     *
     * @return int The duration (in seconds) of the event.
     */
    public function duration()
    {
        return $this->_end->timestamp() - $this->_start->timestamp();
    }

    /**
     * Return event details encoded for integration into the free/busy output.
     *
     * @return array The encoded free/busy information.
     */
    public function getEncodedInformation()
    {
        return array(
            'X-UID'      => base64_encode($this->_event_id),
            'X-SUMMARY'  => base64_encode($this->_private ? '' : $this->_title),
            'X-LOCATION' => base64_encode($this->_private ? '' : $this->_location)
        );
    }

    /**
     * Retrieve the busy times from this event within the given timeframe.  This
     * is trivial for non-recurring events but recurring events need to be
     * expanded.
     *
     * @param Horde_Date $startDate The start point.
     * @param Horde_Date $endDate   The end point.
     *
     * @return array The list of busy times (only the start times of the event).
     */
    public function getBusyTimes(Horde_Date $startDate, Horde_Date $endDate)
    {
        if (!$this->recurs()) {
            if ($startDate->compareDateTime($this->_start) > 0 ||
                $endDate->compareDateTime($this->_start) < 0) {
                return array();
            }
            return array($this->_start->timestamp());
        } else {
            $result = array();
            $next = $this->_recurrence->nextRecurrence($startDate);
            while ($next) {
                if ($endDate->compareDateTime($next) < 0) {
                    break;
                }
                if (!$this->_recurrence->hasException($next->year, $next->month, $next->mday)) {
                    $result[] = $next->timestamp();
                }
                $next->mday++;
                $next = $this->_recurrence->nextRecurrence($next);
            }
            return $result;
        }
    }
}
