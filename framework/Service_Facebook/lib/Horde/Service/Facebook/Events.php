<?php
/**
 * Events methods for Horde_Service_Facebook
 *
 * Copyright 2009 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package Horde_Service_Facebook
 */
 class Horde_Service_Facebook_Events extends Horde_Service_Facebook_Base
 {
    /**
     * Returns events according to the filters specified.
     *
     * @param int $uid            (Optional) User associated with events. A null
     *                            parameter will default to the session user.
     * @param string $eids        (Optional) Filter by these comma-separated event
     *                            ids. A null parameter will get all events for
     *                            the user.
     * @param int $start_time     (Optional) Filter with this unix time as lower
     *                            bound.  A null or zero parameter indicates no
     *                            lower bound.
     * @param int $end_time       (Optional) Filter with this UTC as upper bound.
     *                            A null or zero parameter indicates no upper
     *                            bound.
     * @param string $rsvp_status (Optional) Only show events where the given uid
     *                            has this rsvp status.  This only works if you
     *                            have specified a value for $uid.  Values are as
     *                            in events.getMembers.  Null indicates to ignore
     *                            rsvp status when filtering.
     *
     * @return array  The events matching the query.
     */
    public function &get($uid = null, $eids = null, $start_time = null,
                                $end_time = null, $rsvp_status = null)
    {
        // Note we return a reference to support batched calls
        //  (see Horde_Service_Facebook::call_method)
        return $this->_facebook->call_method('facebook.events.get',
            array('uid' => $uid,
                  'eids' => $eids,
                  'start_time' => $start_time,
                  'end_time' => $end_time,
                  'rsvp_status' => $rsvp_status,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Returns membership list data associated with an event.
     *
     * @param int $eid  event id
     *
     * @return array  An assoc array of four membership lists, with keys
     *                'attending', 'unsure', 'declined', and 'not_replied'
     */
    public function &getMembers($eid)
    {
        return $this->_facebook->call_method('facebook.events.getMembers',
                                             array('eid' => $eid,
                                                   'session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * RSVPs the current user to this event.
     *
     * @param int $eid             event id
     * @param string $rsvp_status  'attending', 'unsure', or 'declined'
     *
     * @return bool  true if successful
     */
    public function &rsvp($eid, $rsvp_status)
    {
        return $this->_facebook->call_method('facebook.events.rsvp',
            array('eid' => $eid,
                  'rsvp_status' => $rsvp_status,
                   'session_key' => $this->_facebook->auth->getSessionKey()));
    }


    /**
     * Cancels an event. Only works for events where application is the admin.
     *
     * @param int $eid                event id
     * @param string $cancel_message  (Optional) message to send to members of
     *                                the event about why it is cancelled
     *
     * @return bool  true if successful
     */
    public function &cancel($eid, $cancel_message = '')
    {
        return $this->_facebook->call_method('facebook.events.cancel',
            array('eid' => $eid,
                  'cancel_message' => $cancel_message,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Creates an event on behalf of the user is there is a session, otherwise on
     * behalf of app.  Successful creation guarantees app will be admin.
     *
     * @param assoc array $event_info  json encoded event information
     *
     * @return int  event id
     */
    public function &create($event_info)
    {
        return $this->_facebook->call_method('facebook.events.create',
            array('event_info' => $event_info,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

    /**
     * Edits an existing event. Only works for events where application is admin.
     *
     * @param int $eid                 event id
     * @param assoc array $event_info  json encoded event information
     *
     * @return bool  true if successful
     */
    public function &edit($eid, $event_info)
    {
        return $this->_facebook->call_method('facebook.events.edit',
            array('eid' => $eid,
                  'event_info' => $event_info,
                  'session_key' => $this->_facebook->auth->getSessionKey()));
    }

 }