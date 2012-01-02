<?php
/**
 * Events methods for Horde_Service_Facebook
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Service_Facebook
 */
 class Horde_Service_Facebook_Events extends Horde_Service_Facebook_Base
 {
    /**
     * Returns events according to the filters specified.
     *
     * @param integer $uid        User associated with events. A null parameter
     *                            will default to the session user.
     * @param string $eids        Filter by these comma-separated event ids. A
     *                            null parameter will get all events for the
     *                            user.
     * @param integer $start_time Filter with this unix time as lower bound.
     *                            A null or zero parameter indicates no lower
     *                            bound.
     * @param integer $end_time   Filter with this UTC as upper bound. A null
     *                            or zero parameter indicates no upper bound.
     * @param string $rsvp_status Only show events where the given uid has this
     *                            rsvp status.  This only works if you have
     *                            specified a value for $uid.  Values are as
     *                            in events.getMembers.  Null indicates to
     *                            ignore rsvp status when filtering.
     *
     * @return array  The events matching the query.
     */
    public function &get($uid = null, $eids = null, $start_time = null,
                         $end_time = null, $rsvp_status = null)
    {
        if (empty($uid)) {
            $uid = 'me()';//$this->_facebook->auth->getLoggedInUser();
        }

        $fql = 'SELECT eid, name, tagline, nid, pic_square, pic_small, '
            . 'pic_big, pic, host, description, event_type, event_subtype, '
            . 'start_time, end_time, creator, update_time, location, venue, '
            . 'privacy, hide_guest_list FROM event WHERE eid IN';

        if (!empty($rsvp_status)) {
            $fql .= '(SELECT eid FROM event_member WHERE uid=' . $uid . ' AND rsvp_status=\'' . $rsvp_status . '\')';
        } else {
            $fql .= '(SELECT eid FROM event_member WHERE uid=' . $uid . ')';
        }

        if (!empty($eids)) {
            $fql .= ' AND eid IN (' . implode(',', $eids) . ')';
        }

        if (!empty($start_time)) {
            $fql .= ' AND start_time>=' . $start_time;
        }
        if (!empty($end_time)) {
            $fql .= ' AND start_time<=' . $end_time;
        }

        // Get the events
        $events = $this->_facebook->fql->run($fql);

        // If no requested status, query to get the current statuses.
        if (empty($rsvp_status)) {
           $eids = array();
            foreach ($events as $e) {
                $eids[] = $e['eid'];
            }
            $fql = 'SELECT eid, rsvp_status FROM event_member WHERE uid=' . $uid
                . 'AND eid IN (' . implode(',', $eids) . ')';

            $status = $this->_facebook->fql->run($fql);
            foreach ($events as &$e) {
                foreach ($status as $s) {
                    if ($s['eid'] == $e['eid']) {
                        $e['rsvp_status'] = $this->_fromDriverStatus($s['rsvp_status']);
                    }
                }
            }
        } else {
            // Otherwise, we already know the status.
            foreach ($events as &$e) {
                $e['rsvp_status'] = $this->_fromDriverStatus($rsvp_status);
            }
        }

        return $events;
    }

    protected function _fromDriverStatus($driver_status)
    {
        switch ($driver_status) {
        case 'attending':
            return 'confirmed';
        case 'unsure':
            return 'tentative';
        case 'declined':
        case 'not_replied':
            return 'free';
        }
    }

    /**
     * Returns membership list data associated with an event.
     *
     * @param integer $eid  event id
     *
     * @return array  An assoc array of four membership lists, with keys
     *                'attending', 'unsure', 'declined', and 'not_replied'
     */
    public function &getMembers($eid)
    {
        return $this->_facebook->callMethod(
            'facebook.events.getMembers',
             array('eid' => $eid));
    }

    /**
     * RSVPs the current user to this event.
     *
     * @param integer $eid           event id
     * @param string  $rsvp_status  'attending', 'unsure', or 'declined'
     *
     * @return boolean
     */
    public function &rsvp($eid, $rsvp_status)
    {
        return $this->_facebook->callMethod(
            'facebook.events.rsvp',
            array('eid' => $eid,
                  'rsvp_status' => $rsvp_status));
    }


    /**
     * Cancels an event. Only works for events where application is the admin.
     *
     * @param integer $eid            event id
     * @param string $cancel_message  (Optional) message to send to members of
     *                                the event about why it is cancelled
     *
     * @return boolean
     */
    public function &cancel($eid, $cancel_message = '')
    {
        return $this->_facebook->callMethod(
            'facebook.events.cancel',
            array('eid' => $eid,
                  'cancel_message' => $cancel_message));
    }

    /**
     * Creates an event on behalf of the user is there is a session, otherwise on
     * behalf of app.  Successful creation guarantees app will be admin.
     *
     * @param array $event_info  json encoded event information
     *
     * @return integer  event id
     */
    public function &create(array $event_info)
    {
        return $this->_facebook->callMethod(
            'facebook.events.create',
            array('event_info' => $event_info));
    }

    /**
     * Edits an existing event. Only works for events where application is admin.
     *
     * @param integer $eid         event id
     * @param array   $event_info  json encoded event information
     *
     * @return boolean  true if successful
     */
    public function &edit($eid, array $event_info)
    {
        return $this->_facebook->callMethod(
            'facebook.events.edit',
            array('eid' => $eid,
                  'event_info' => $event_info));
    }

 }