<?php

/**
 * A reduced event representation derived from the Kronolith event
 * representation.
 *
 * $Horde: framework/Kolab_FreeBusy/lib/Horde/Kolab/FreeBusy/Imap.php,v 1.10 2009/07/14 00:28:33 mrubinsk Exp $
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Object_Event {

    /** Event status - Taken from Kronolith */
    const STATUS_NONE      = 0;
    const STATUS_TENTATIVE = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_CANCELLED = 3;
    const STATUS_FREE      = 4;

    /**
     * The driver unique identifier for this event.
     *
     * @var string
     */
    var $eventID = null;

    /**
     * The start time of the event.
     *
     * @var Horde_Date
     */
    var $start;

    /**
     * The end time of the event.
     *
     * @var Horde_Date
     */
    var $end;

    /**
     * The title of this event.
     *
     * @var string
     */
    var $title = '';

    /**
     * The location this event occurs at.
     *
     * @var string
     */
    var $location = '';

    /**
     * Whether the event is private.
     *
     * @var boolean
     */
    var $private = false;

    public function __construct(array $event)
    {
        $this->eventID = $event['uid'];

        $this->start = new Horde_Date($event['start-date']);
        $this->end = new Horde_Date($event['end-date']);

        if (isset($event['summary'])) {
            $this->title = $event['summary'];
        }

        if (isset($event['location'])) {
            $this->location = $event['location'];
        }

        if ($event['sensitivity'] == 'private' || $event['sensitivity'] == 'confidential') {
            $this->private = true;
        }

        if (isset($event['show-time-as'])) {
            switch ($event['show-time-as']) {
                case 'free':
                    $this->status = self::STATUS_FREE;
                    break;

                case 'tentative':
                    $this->status = self::STATUS_TENTATIVE;
                    break;

                case 'busy':
                case 'outofoffice':
                default:
                    $this->status = self::STATUS_CONFIRMED;
            }
        } else {
            $this->status = self::STATUS_CONFIRMED;
        }

        // Recurrence
        if (isset($event['recurrence'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->fromHash($event['recurrence']);
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
        $next = $this->recurrence->nextRecurrence($startDate);
        while ($next !== false &&
               $this->recurrence->hasException($next->year, $next->month, $next->mday)) {
            $next->mday++;
            $next = $this->recurrence->nextRecurrence($next);
        }

        if ($next !== false) {
            $duration = $next->timestamp() - $this->start->timestamp();
            $next_end = new Horde_Date($this->end->timestamp() + $duration);

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
     * @return boolean  True if this is a recurring event.
     */
    function recurs()
    {
        return isset($this->recurrence) &&
            !$this->recurrence->hasRecurType(Horde_Date_Recurrence::RECUR_NONE);
    }

    /**
     * Sets the global UID for this event.
     *
     * @param string $uid  The global UID for this event.
     */
    function setUID($uid)
    {
        $this->_uid = $uid;
    }

    /**
     * Checks whether the events status is the same as the specified value.
     *
     * @param integer $status  The status value to check against.
     *
     * @return boolean  True if the events status is the same as $status.
     */
    function hasStatus($status)
    {
        return ($status == $this->status);
    }

    public function duration()
    {
        return $this->end->timestamp() - $this->start->timestamp();
    }

    public function getEncodedInformation()
    {
        return array(
            'X-UID'      => base64_encode($this->eventID),
            'X-SUMMARY'  => base64_encode($this->private ? '' : $this->title),
            'X-LOCATION' => base64_encode($this->private ? '' : $this->location)
        );
    }

    public function isFree()
    {
        return (
            $this->status == self::STATUS_FREE ||
            $this->status == self::STATUS_CANCELLED
        );
    }

    public function getBusyTimes(Horde_Date $startDate, Horde_Date $endDate)
    {
        if ($this->isFree()) {
            return array();
        }

        if (!$this->recurs()) {
            return array($this->start->timestamp());
        } else {
            $result = array();
            $next = $this->recurrence->nextRecurrence($startDate);
            while ($next) {
                if ($endDate->compareDateTime($next) < 0) {
                    break;
                }
                if (!$this->recurrence->hasException($next->year, $next->month, $next->mday)) {
                    $result[] = $next->timestamp();
                }
                $next->mday++;
                $next = $this->recurrence->nextRecurrence($next);
            }
        }
    }
}
