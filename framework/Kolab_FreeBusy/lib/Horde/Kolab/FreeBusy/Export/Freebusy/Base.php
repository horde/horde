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
class Horde_Kolab_FreeBusy_Export_Freebusy_Base
{

    public function __construct(
        Horde_Kolab_FreeBusy_Export_Freebusy_Backend $backend,
        Horde_Kolab_FreeBusy_Resource $resource,
        Horde_Controller_Request_Base $request
    ) {
        $this->_resource = $resource;
    }

    public function getStart()
    {
        $start = new Horde_Date();
        $start->mday = $start->mday - $this->_resource->getOwner()->getFreeBusyPast();
        return $start;
    }

    public function getEnd()
    {
        $end = new Horde_Date();
        $end->mday = $end->mday - $this->_resource->getOwner()->getFreeBusyFuture();
        return $end;
    }

    public function getResourceName()
    {
        return $this->_resource->getName();
    }

    public function getOrganizerMail()
    {
        return 'MAILTO:' . $this->_resource->getOwner()->getMail();
    }

    public function getOrganizerName()
    {
        $params = array();
        $name = $this->_resource->getOwner()->getName();
        if (!empty($name)) {
            $params['cn'] = $name;
        }
        return $params;
    }

    public function getDateStamp()
    {
        return $this->_request->getServer('REQUEST_TIME');
    }

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
    public function export()
    {
        /* Create the new iCalendar. */
        $vCal = new Horde_iCalendar();
        $vCal->setAttribute('PRODID', $this->_backend->getProductId());
        $vCal->setAttribute('METHOD', 'PUBLISH');

        /* Create the new vFreebusy component. */
        $vFb = &Horde_iCalendar::newComponent('vfreebusy', $vCal);

        $vFb->setAttribute(
            'ORGANIZER', $this->getOrganizerMail(), $this->getOrganizerName()
        );
        $vFb->setAttribute('DTSTAMP', $this->getDateStamp());
        $vFb->setAttribute('DTSTART', $this->getStart()->timestamp());
        $vFb->setAttribute('DTEND', $this->getEnd()->timestamp());
        $url = $this->_backend->getUrl();
        if (!empty($url)) {
            $vFb->setAttribute('URL', $this->getUrl());
        }

        /* Add all the busy periods. */
        foreach (
            $this->_resource->listEvents($this->getStart(), $this->getEnd())
            as $event
        ) {
            foreach (
                $event->getBusyTimes($this->getStart(), $this->getEnd())
                as $busy
            ) {
                $vFb->addBusyPeriod(
                    'BUSY',
                    $busy,
                    null,
                    $event->duration(),
                    $event->getEncodedInformation()
                );
            }
        }

        /* Remove the overlaps. */
        $vFb->simplify();

        /* Combine and return. */
        $vCal->addComponent($vFb);
        return $vCal;
    }
}
