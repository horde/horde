<?php

/** Event status - Taken from Kronolith*/
define('KRONOLITH_STATUS_NONE', 0);
define('KRONOLITH_STATUS_TENTATIVE', 1);
define('KRONOLITH_STATUS_CONFIRMED', 2);
define('KRONOLITH_STATUS_CANCELLED', 3);
define('KRONOLITH_STATUS_FREE', 4);

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
class Kolab_Event {

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

    function Kolab_Event($event)
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
                    $this->status = KRONOLITH_STATUS_FREE;
                    break;

                case 'tentative':
                    $this->status = KRONOLITH_STATUS_TENTATIVE;
                    break;

                case 'busy':
                case 'outofoffice':
                default:
                    $this->status = KRONOLITH_STATUS_CONFIRMED;
            }
        } else {
            $this->status = KRONOLITH_STATUS_CONFIRMED;
        }

        // Recurrence
        if (isset($event['recurrence'])) {
            $this->recurrence = new Horde_Date_Recurrence($this->start);
            $this->recurrence->fromHash($event['recurrence']);
        }

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

}
