<?php
/**
 * Free/Busy functionality.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_FreeBusy
{
    /**
     * Generates the free/busy text for $calendars. Cache it for at least an
     * hour, as well.
     *
     * @param string|array $calendars  The calendar to view free/busy slots for.
     * @param integer $startstamp      The start of the time period to retrieve.
     * @param integer $endstamp        The end of the time period to retrieve.
     * @param boolean $returnObj       Default false. Return a vFreebusy object
     *                                 instead of text.
     * @param string $user             Set organizer to this user.
     *
     * @return string  The free/busy text.
     * @throws Horde_Exception
     */
    public static function generate($calendars, $startstamp = null,
                                    $endstamp = null, $returnObj = false,
                                    $user = null)
    {
        if (!is_array($calendars)) {
            $calendars = array($calendars);
        }

        if (!$user) {
            $kronolith_shares = $GLOBALS['injector']->getInstance('Kronolith_Shares');

            /* Find a share and retrieve owner. */
            foreach ($calendars as $calendar) {
                if (strpos($calendar, 'internal_') !== 0) {
                    continue;
                }
                $calendar = substr($calendar, 9);
                try {
                    $share = $kronolith_shares->getShare($calendar);
                    $user = $share->get('owner');
                    break;
                } catch (Horde_Exception $e) {
                }
            }
        }

        /* Default the start date to today. */
        if (is_null($startstamp)) {
            $startstamp = mktime(0, 0, 0);
        }

        /* Default the end date to the start date + freebusy_days. */
        if (is_null($endstamp) || $endstamp < $startstamp) {
            $enddate = new Horde_Date($startstamp);
            $enddate->mday += $GLOBALS['prefs']->getValue('freebusy_days');
            $endstamp = $enddate->timestamp();
        } else {
            $enddate = new Horde_Date($endstamp);
        }

        /* Get the Identity for the owner of the share. */
        $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create($user);
        $email = $identity->getValue('from_addr');
        $cn = $identity->getValue('fullname');
        if (empty($mail) && empty($cn)) {
            $cn = $user;
        }

        /* Fetch events. */
        $busy = array();
        foreach ($calendars as $calendar) {
            if (strpos($calendar, '_')) {
                @list($type, $calendar) = explode('_', $calendar, 2);
            } else {
                $type = 'internal';
            }
            try {
                $driver = Kronolith::getDriver($type, $calendar);
                $events = $driver->listEvents(
                    new Horde_Date($startstamp),
                    $enddate,
                    array('show_recurrence' => true));
                Kronolith::mergeEvents($busy, $events);
            } catch (Exception $e) {
            }
        }

        /* Create the new iCalendar. */
        $vCal = new Horde_Icalendar();
        $vCal->setAttribute('PRODID', '-//The Horde Project//Kronolith ' . $GLOBALS['registry']->getVersion() . '//EN');
        $vCal->setAttribute('METHOD', 'PUBLISH');

        /* Create new vFreebusy. */
        $vFb = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        $params = array();
        if (!empty($cn)) {
            $params['CN'] = $cn;
        }
        if (!empty($email)) {
            $vFb->setAttribute('ORGANIZER', 'mailto:' . $email, $params);
        } else {
            $vFb->setAttribute('ORGANIZER', '', $params);
        }

        $vFb->setAttribute('DTSTAMP', $_SERVER['REQUEST_TIME']);
        $vFb->setAttribute('DTSTART', $startstamp);
        $vFb->setAttribute('DTEND', $endstamp);
        $vFb->setAttribute('URL', Horde::url('fb.php?u=' . $user, true, -1));

        /* Add all the busy periods. */
        foreach ($busy as $events) {
            foreach ($events as $event) {
                if ($event->status == Kronolith::STATUS_FREE) {
                    continue;
                }
                if ($event->status == Kronolith::STATUS_CANCELLED) {
                    continue;
                }

                /* Horde_Icalendar_Vfreebusy only supports timestamps at the
                 * moment. */
                $vFb->addBusyPeriod('BUSY', $event->start->timestamp(), null,
                                    $event->end->timestamp() - $event->start->timestamp());
            }
        }

        /* Remove the overlaps. */
        $vFb->simplify();
        $vCal->addComponent($vFb);

        /* Return the vFreebusy object if requested. */
        if ($returnObj) {
            return $vFb;
        }

        /* Generate the vCal file. */
        return $vCal->exportvCalendar();
    }

    /**
     * Retrieves the free/busy information for a given email address, if any
     * information is available.
     *
     * @param string $email  The email address to look for.
     * @param boolean $json  Whether to return the free/busy data as a simple
     *                       object suitable to be transferred as json.
     *
     * @return Horde_Icalendar_Vfreebusy  Free/busy component.
     * @throws Kronolith_Exception
     */
    public static function get($email, $json = false)
    {
        $default_domain = empty($GLOBALS['conf']['storage']['default_domain']) ? null : $GLOBALS['conf']['storage']['default_domain'];
        $rfc822 = new Horde_Mail_Rfc822();

        try {
            $res = $rfc822->parseAddressList($email, array(
                'default_domain' => $default_domain
            ));
        } catch (Horde_Mail_Exception $e) {
            throw new Kronolith_Exception($e);
        }

        if (!($tmp = $res[0])) {
            throw new Kronolith_Exception(_("No valid email address found"));
        }

        $email = $tmp->bare_address;

        /* Check if we can retrieve a VFB from the Free/Busy URL, if one is
         * set. */
        $url = self::getUrl($email);
        if ($url) {
            $url = trim($url);
            $http = $GLOBALS['injector']->getInstance('Horde_Core_Factory_HttpClient')->create();
            try {
                $response = $http->get($url);
            } catch (Horde_Http_Client_Exception $e) {
                throw new Kronolith_Exception(sprintf(_("The free/busy url for %s cannot be retrieved."), $email));
            }
            if ($response->code == 200 && $data = $response->getBody()) {
                // Detect the charset of the iCalendar data.
                $contentType = $response->getHeader('Content-Type');
                if ($contentType && strpos($contentType, ';') !== false) {
                    list(,$charset,) = explode(';', $contentType);
                    $data = Horde_String::convertCharset($data, trim(str_replace('charset=', '', $charset)), 'UTF-8');
                }

                $vCal = new Horde_Icalendar();
                $vCal->parsevCalendar($data, 'VCALENDAR');
                $components = $vCal->getComponents();

                $vCal = new Horde_Icalendar();
                $vFb = Horde_Icalendar::newComponent('vfreebusy', $vCal);
                $vFb->setAttribute('ORGANIZER', $email);
                $found = false;
                foreach ($components as $component) {
                    if ($component instanceof Horde_Icalendar_Vfreebusy) {
                        $found = true;
                        $vFb->merge($component);
                    }
                }

                if ($found) {
                    // @todo: actually store the results in the storage, so
                    // that they can be retrieved later. We should store the
                    // plain iCalendar data though, to avoid versioning
                    // problems with serialize iCalendar objects.
                    return $json ? self::toJson($vFb) : $vFb;
                }
            }
        }

        /* Check storage driver. */
        $storage = $GLOBALS['injector']->getInstance('Kronolith_Factory_Storage')->create();

        try {
            $fb = $storage->search($email);
            return $json ? self::toJson($fb) : $fb;
        } catch (Horde_Exception_NotFound $e) {
            if ($url) {
                throw new Kronolith_Exception(sprintf(_("No free/busy information found at the free/busy url of %s."), $email));
            }
            throw new Kronolith_Exception(sprintf(_("No free/busy url found for %s."), $email));
        }

        /* Or else return an empty VFB object. */
        $vCal = new Horde_Icalendar();
        $vFb = Horde_Icalendar::newComponent('vfreebusy', $vCal);
        $vFb->setAttribute('ORGANIZER', $email);

        return $json ? self::toJson($vFb) : $vFb;
    }

    /**
     * Searches address books for the freebusy URL for a given email address.
     *
     * @param string $email  The email address to look for.
     *
     * @return mixed  The url on success or false on failure.
     */
    public static function getUrl($email)
    {
        $sources = json_decode($GLOBALS['prefs']->getValue('search_sources'));
        if (empty($sources)) {
            $sources = array();
        }

        try {
            $result = $GLOBALS['registry']->call('contacts/getField',
                                                 array($email, 'freebusyUrl', $sources, true, true));
        } catch (Horde_Exception $e) {
            return false;
        }
        if (is_array($result)) {
            return array_shift($result);
        }

        return $result;
    }

    /**
     * Converts free/busy data to a simple object suitable to be transferred
     * as json.
     *
     * @param Horde_Icalendar_Vfreebusy $fb  A Free/busy component.
     *
     * @return object  A simple object representation.
     */
    public static function toJson(Horde_Icalendar_Vfreebusy $fb)
    {
        $json = new stdClass;
        $start = $fb->getStart();
        if ($start) {
            $start = new Horde_Date($start);
            $json->s = $start->dateString();
        }
        $end = $fb->getEnd();
        if ($end) {
            $end = new Horde_Date($end);
            $json->e = $end->dateString();
        }
        $b = $fb->getBusyPeriods();
        if (empty($b)) {
            $b = new StdClass();
        }
        $json->b = $b;
        return $json;
    }

}
