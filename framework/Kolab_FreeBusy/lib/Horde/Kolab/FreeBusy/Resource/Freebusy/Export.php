<?php
/**
 * Converts the data from the free/busy resource into a free/busy iCal object,
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Converts the data from the free/busy resource into a free/busy iCal object,
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Freebusy_Export
{

    /**
     * Generates the free/busy text for $calendar. Cache it for at least an
     * hour, as well.
     *
     * @param integer $startstamp     The start of the time period to retrieve.
     * @param integer $endstamp       The end of the time period to retrieve.
     * @param integer $fbpast         The number of days that free/busy should
     *                                be calculated for the past
     * @param integer $fbfuture       The number of days that free/busy should
     *                                be calculated for the future
     * @param string  $user           Set organizer to this user.
     * @param string  $cn             Set the common name of this user.
     *
     * @return Horde_iCalendar  The iCal object or a PEAR error.
     */
    function export($startstamp = null, $endstamp = null,
		    $fbpast = 0, $fbfuture = 60,
		    $user = null, $cn = null)
    {
        /* Get the iCalendar library at this point */
        require_once 'Horde/iCalendar.php';

        /* Default the start date to today. */
        if (is_null($startstamp)) {
            $month = date('n');
            $year = date('Y');
            $day = date('j');

            $startstamp = mktime(0, 0, 0, $month, $day - $fbpast, $year);
        }

        /* Default the end date to the start date + freebusy_days. */
        if (is_null($endstamp) || $endstamp < $startstamp) {
            $month = date('n');
            $year = date('Y');
            $day = date('j');

            $endstamp = mktime(0, 0, 0,
                               $month,
                               $day + $fbfuture,
                               $year);
        }

        Horde::logMessage(sprintf('Creating free/busy information from %s to %s',
                                  $startstamp, $endstamp), __FILE__, __LINE__,
                          PEAR_LOG_DEBUG);

        /* Fetch events. */
        $startDate = new Horde_Date($startstamp);
        $endDate = new Horde_Date($endstamp);
        $events = $this->listEvents($startDate, $endDate);
        if (is_a($events, 'PEAR_Error')) {
            return $events;
        }

        /* Create the new iCalendar. */
        $vCal = new Horde_iCalendar();
        $vCal->setAttribute('PRODID', '-//kolab.org//NONSGML Kolab Server 2//EN');
        $vCal->setAttribute('METHOD', 'PUBLISH');

        /* Create new vFreebusy. */
        $vFb = &Horde_iCalendar::newComponent('vfreebusy', $vCal);
        $params = array();
        if ($cn) {
            $params['cn'] = $cn;
        }
        $vFb->setAttribute('ORGANIZER', 'MAILTO:' . $user, $params);

        $vFb->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
        $vFb->setAttribute('DTSTART', $startstamp);
        $vFb->setAttribute('DTEND', $endstamp);
        // URL is not required, so out it goes...
        //$vFb->setAttribute('URL', Horde::applicationUrl('fb.php?u=' . $share->get('owner'), true, -1));

        /* Add all the busy periods. */
        foreach ($events as $event) {
            if ($event->hasStatus(KRONOLITH_STATUS_FREE)) {
                continue;
            }

            $duration = $event->end->timestamp() - $event->start->timestamp();
            $extra = array('X-UID'      => base64_encode($event->eventID),
                           'X-SUMMARY'  => base64_encode($event->private ? '' : $event->title),
                           'X-LOCATION' => base64_encode($event->private ? '' : $event->location));

            /* Make sure that we're using the current date for recurring
             * events. */
            if ($event->recurs()) {
                $startThisDay = mktime($event->start->hour,
                                       $event->start->min,
                                       $event->start->sec,
                                       date('n', $day),
                                       date('j', $day),
                                       date('Y', $day));
            } else {
                $startThisDay = $event->start->timestamp($extra);
            }
            if (!$event->recurs()) {
                $vFb->addBusyPeriod('BUSY', $startThisDay, null, $duration, $extra);
            } else {
                $next = $event->recurrence->nextRecurrence($startDate);
                while ($next) {
                    if ($endDate->compareDateTime($next) < 0) {
                        break;
                    }
                    if (!$event->recurrence->hasException($next->year, $next->month, $next->mday)) {
                        $vFb->addBusyPeriod('BUSY', $next->timestamp(), null, $duration, $extra);
                    }
                    $next->mday++;
                    $next = $event->recurrence->nextRecurrence($next);
                }
            }
        }

        /* Remove the overlaps. */
        $vFb->simplify();
        $vCal->addComponent($vFb);

        return $vCal;
    }
}
