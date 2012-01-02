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
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Converts the data from the free/busy resource into a free/busy iCal object,
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 * Copyright 2011 Kolab Systems AG
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Export_Freebusy_Base
implements Horde_Kolab_FreeBusy_Export_Freebusy
{
    /**
     * The resource to export.
     *
     * @var Horde_Kolab_FreeBusy_Resource
     */
    private $_resource;

    /**
     * The backend definition.
     *
     * @var Horde_Kolab_FreeBusy_Export_Freebusy_Backend
     */
    private $_backend;

    /**
     * Additional parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * The event status to free/busy status mapper.
     *
     * @var Horde_Kolab_FreeBusy_Helper_StatusMap
     */
    private $_status_map;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Export_Freebusy_Backend $backend  The export backend.
     * @param Horde_Kolab_FreeBusy_Resource                $resource The resource to export.
     * @param array                                        $params   Additional parameters.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Export_Freebusy_Backend $backend,
        Horde_Kolab_FreeBusy_Resource $resource,
        $params
    ) {
        if (!isset($params['future_days'])) {
            $params['future_days'] = 60;
        }
        if (!isset($params['past_days'])) {
            $params['past_days'] = 0;
        }
        if (!isset($params['request_time'])) {
            $params['request_time'] = (string) new Horde_Date();
        }
        if (!isset($params['status_map'])) {
            $this->_status_map = new Horde_Kolab_FreeBusy_Helper_Freebusy_StatusMap_Default();
        } else {
            $this->_status_map = $params['status_map'];
        }
        $this->_resource = $resource;
        $this->_backend  = $backend;
        $this->_params   = $params;
    }

    /**
     * Return today as Horde_Date.
     *
     * @return Horde_Date Today.
     */
    private function _today()
    {
        return new Horde_Date(
            array(
                'year' => date('Y'),
                'month' => date('n'),
                'mday' => date('j')
            )
        );
    }

    /**
     * Get the start timestamp for the export.
     *
     * @return Horde_Date The start timestamp for the export.
     */
    public function getStart()
    {
        try {
            $past = $this->_resource->getOwner()->getFreeBusyPast();
        } catch (Horde_Kolab_FreeBusy_Exception $e) {
            $past = $this->_params['past_days'];
        }
        $start = $this->_today();
        $start->mday = $start->mday - $past;
        return $start;
    }

    /**
     * Get the end timestamp for the export.
     *
     * @return Horde_Date The end timestamp for the export.
     */
    public function getEnd()
    {
        try {
            $future = $this->_resource->getOwner()->getFreeBusyFuture();
        } catch (Horde_Kolab_FreeBusy_Exception $e) {
            $future = $this->_params['future_days'];
        }
        $end = $this->_today();
        $end->mday = $end->mday + $future;
        return $end;
    }

    /**
     * Get the name of the resource.
     *
     * @return string The name of the resource.
     */
    public function getResourceName()
    {
        return $this->_resource->getName();
    }

    /**
     * Return the organizer mail for the export.
     *
     * @return string The organizer mail.
     */
    public function getOrganizerMail()
    {
        return 'MAILTO:' . $this->_resource->getOwner()->getMail();
    }

    /**
     * Return the organizer name for the export.
     *
     * @return string The organizer name.
     */
    public function getOrganizerName()
    {
        $params = array();
        $name = $this->_resource->getOwner()->getName();
        if (!empty($name)) {
            $params['cn'] = $name;
        }
        return $params;
    }

    /**
     * Return the timestamp for the export.
     *
     * @return string The timestamp.
     */
    public function getDateStamp()
    {
        if (isset($this->_params['request_time'])) {
            return $this->_params['request_time'];
        } else {
            return (string) new Horde_Date();
        }
    }

    /**
     * Generates the free/busy export.
     *
     * @return Horde_iCalendar  The iCal object.
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
            $status = $this->_status_map->map($event->getStatus());
            $duration = $event->duration();
            $extra = $event->getEncodedInformation();
            foreach (
                $event->getBusyTimes($this->getStart(), $this->getEnd())
                as $busy
            ) {
                $vFb->addBusyPeriod($status, $busy, null, $duration, $extra);
            }
        }

        /* Remove the overlaps. */
        $vFb->simplify();

        /* Combine and return. */
        $vCal->addComponent($vFb);
        return $vCal;
    }
}
