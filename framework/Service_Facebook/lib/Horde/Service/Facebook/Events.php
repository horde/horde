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
     * @param array $options  The filter options:
     *   - uid: (string)  The user id.
     *   - eids: (string) Comma delimited list of event ids to filter on.
     *   - start: (integer) Only return events occuring after this timestamp.
     *   - end: (integet)  Only return events occuring before this timestamp.
     *   - rsvp: (string)  Only return events if uid is specified and the user
     *                     has this rsvp status.
     *
     * @return array  The events matching the query.
     */
    public function get($options = array())
    {
        $defaults = array('uid' => 'me()');
        $options = array_merge($defaults, $options);

        $fql = 'SELECT eid, name, tagline, nid, pic_square, pic_small, '
            . 'pic_big, pic, host, description, event_type, event_subtype, '
            . 'start_time, end_time, creator, update_time, location, venue, '
            . 'privacy, hide_guest_list FROM event WHERE eid IN';

        if (!empty($options['rsvp'])) {
            $fql .= '(SELECT eid FROM event_member WHERE uid=' . $options['uid']
                . ' AND rsvp_status=\'' . $options['rsvp'] . '\')';
        } else {
            $fql .= '(SELECT eid FROM event_member WHERE uid=' . $options['uid'] . ')';
        }

        if (!empty($options['eids'])) {
            $fql .= ' AND eid IN (' . implode(',', $options['eids']) . ')';
        }

        if (!empty($options['start'])) {
            $fql .= ' AND start_time>=' . $options['start'];
        }
        if (!empty($options['end'])) {
            $fql .= ' AND start_time<=' . $options['end'];
        }

        // Get the events
        $events = $this->_facebook->fql->run($fql);

        // If no requested status, query to get the current statuses.
        if (empty($options['rsvp'])) {
           $eids = array();
            foreach ($events as $e) {
                $eids[] = $e['eid'];
            }
            $fql = 'SELECT eid, rsvp_status FROM event_member WHERE uid=' . $options['uid']
                . ' AND eid IN (' . implode(',', $eids) . ')';

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
     * @return array  An array of objects with 'name', 'id', and 'rsvp_status'
     *                values.
     */
    public function getMembers($eid)
    {
        return $this->_facebook->callGraphApi($eid . '/invited');
    }

    /**
     * RSVPs the current user to this event.
     *
     * @param integer $eid    event id
     * @param string  $rsvp  'attending', 'maybe', or 'declined'
     *
     * @return boolean
     */
    public function rsvp($eid, $rsvp)
    {
        if (!in_array($rsvp, array('attending', 'maybe', 'declined'))) {
            throw InvalidArgumentException();
        }

        return $this->_facebook->callGraphApi(
            $eid . '/' . $rsvp,
            array(),
            array('request' => 'POST'));
    }

    /**
     * Cancels an event. Only works for events where application is the admin.
     *
     * @param integer $eid event id
     *
     * @return boolean
     */
    public function cancel($eid, $cancel_message = '')
    {
        return $this->_facebook->callGraphApi(
            $eid,
            array(),
            array('request' => 'DELETE'));
    }

    /**
     * Creates an event on behalf of the user is there is a session, otherwise on
     * behalf of app.  Successful creation guarantees app will be admin.
     *
     * @param string $uid        The facebook id the event is attached to.
     * @param array $event_info  json encoded event information
     *
     * @return integer  event id
     */
    public function create($uid, array $event_info)
    {
        return $this->_facebook->callGraphApi(
            $uid . '/events',
            $event_info,
            array('request' => 'POST'));
    }

 }