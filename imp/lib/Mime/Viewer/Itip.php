<?php
/**
 * Displays vCalendar/iCalendar data and provides an option to import the data
 * into a calendar source, if available.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Itip extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();

        if (!empty($ret)) {
            reset($ret);
            Horde::startBuffer();
            $GLOBALS['page_output']->header();
            echo $ret[key($ret)]['data'];
            $GLOBALS['page_output']->footer();
            $ret[key($ret)]['data'] = Horde::endBuffer();
        }

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $data = $this->_mimepart->getContents();
        $mime_id = $this->_mimepart->getMimeId();

        // Parse the iCal file.
        $vCal = new Horde_Icalendar();
        if (!$vCal->parsevCalendar($data, 'VCALENDAR', $this->_mimepart->getCharset())) {
            $status = new IMP_Mime_Status(_("The calendar data is invalid"));
            $status->action(IMP_Mime_Status::ERROR);
            return array(
                $mime_id => array(
                    'data' => '',
                    'status' => $status,
                    'type' => 'text/html; charset=UTF-8'
                )
            );
        }

        // Check if we got vcard data with the wrong vcalendar mime type.
        $imp_contents = $this->getConfigParam('imp_contents');
        $c = $vCal->getComponentClasses();
        if ((count($c) == 1) && !empty($c['horde_icalendar_vcard'])) {
            return $imp_contents->renderMIMEPart($mime_id, IMP_Contents::RENDER_INLINE, array('type' => 'text/x-vcard'));
        }

        $imple = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Imple')->create('IMP_Ajax_Imple_ItipRequest', array(
            'mailbox' => $imp_contents->getMailbox(),
            'mime_id' => $mime_id,
            'uid' => $imp_contents->getUid()
        ));

        // Get the method type.
        try {
            $method = $vCal->getAttribute('METHOD');
        } catch (Horde_Icalendar_Exception $e) {
            $method = '';
        }

        $out = array();
        foreach ($vCal->getComponents() as $key => $component) {
            switch ($component->getType()) {
            case 'vEvent':
                $out[] = $this->_vEvent($component, $key, $method);
                break;

            case 'vTodo':
                $out[] = $this->_vTodo($component, $key, $method);
                break;

            case 'vTimeZone':
                // Ignore them.
                break;

            case 'vFreebusy':
                $out[] = $this->_vFreebusy($component, $key, $method);
                break;

            // @todo: handle stray vcards here as well.
            default:
                $out[] = sprintf(_("Unhandled component of type: %s"), $component->getType());
                break;
            }
        }

        $view = $this->_getViewOb();
        $view->formid = $imple->getDomId();
        $view->out = implode('', $out);

        return array(
            $mime_id => array(
                'data' => $view->render('base'),
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Generate the html for a vFreebusy.
     */
    protected function _vFreebusy($vfb, $id, $method)
    {
        global $notification, $prefs, $registry;

        $desc = '';
        $sender = $vfb->getName();

        switch ($method) {
        case 'PUBLISH':
            $desc = _("%s has sent you free/busy information.");
            break;

        case 'REQUEST':
            $sender = $this->getConfigParam('imp_contents')->getHeader()->getValue('From');
            $desc = _("%s requests your free/busy information.");
            break;

        case 'REPLY':
            $desc = _("%s has replied to a free/busy request.");
            break;
        }

        $view = $this->_getViewOb();
        $view->desc = sprintf($desc, $sender);

        try {
            $start = $vfb->getAttribute('DTSTART');
            $view->start = is_array($start)
                ? strftime($prefs->getValue('date_format'), mktime(0, 0, 0, $start['month'], $start['mday'], $start['year']))
                : strftime($prefs->getValue('date_format'), $start) . ' ' . date($prefs->getValue('twentyFour') ? ' G:i' : ' g:i a', $start);
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $end = $vfb->getAttribute('DTEND');
            $view->end = is_array($end)
                ? strftime($prefs->getValue('date_format'), mktime(0, 0, 0, $end['month'], $end['mday'], $end['year']))
                : strftime($prefs->getValue('date_format'), $end) . ' ' . date($prefs->getValue('twentyFour') ? ' G:i' : ' g:i a', $end);
        } catch (Horde_Icalendar_Exception $e) {}

        $options = array();
        switch ($method) {
        case 'PUBLISH':
        case 'REPLY':
            if ($registry->hasMethod('calendar/import_vfreebusy')) {
                if ($this->_autoUpdateReply(($method == 'PUBLISH') ? 'auto_update_fbpublish' : 'auto_update_fbreply', $sender)) {
                    try {
                        $registry->call('calendar/import_vfreebusy', array($vfb));
                        $notification->push(_("The user's free/busy information was sucessfully stored."), 'horde.success');
                    } catch (Horde_Exception $e) {
                        $notification->push(sprintf(_("There was an error importing user's free/busy information: %s"), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $options['import'] = _("Remember the free/busy information.");
                }
            } else {
                $options['nosup'] = _("Reply with Not Supported Message");
            }
            break;

        case 'REQUEST':
            if ($registry->hasMethod('calendar/getFreeBusy')) {
                $options['reply'] = _("Reply with requested free/busy information.");
                $options['reply2m'] = _("Reply with free/busy for next 2 months.");
            } else {
                $options['nosup'] = _("Reply with Not Supported Message");
            }

            $options['deny'] = _("Deny request for free/busy information");
            break;
        }

        if (!empty($options)) {
            reset($options);
            $view->options = $options;
            $view->options_id = $id;
        }

        return $view->render('action');
    }

    /**
     * Generate the HTML for a vEvent.
     */
    protected function _vEvent($vevent, $id, $method = 'PUBLISH')
    {
        global $injector, $prefs, $registry, $notification;

        $attendees = null;
        $desc = '';
        $sender = $vevent->organizerName();
        $options = array();

        try {
            if (($attendees = $vevent->getAttribute('ATTENDEE')) &&
                !is_array($attendees)) {
                $attendees = array($attendees);
            }
        } catch (Horde_Icalendar_Exception $e) {}

        switch ($method) {
        case 'PUBLISH':
            $desc = _("%s wishes to make you aware of \"%s\".");
            if ($registry->hasMethod('calendar/import')) {
                $options['import'] = _("Add this to my calendar");
            }
            break;

        case 'REQUEST':
            // Check if this is an update.
            try {
                $registry->call('calendar/export', array($vevent->getAttribute('UID'), 'text/calendar'));

                $desc = _("%s wants to notify you about changes in \"%s\".");
                $is_update = true;
            } catch (Horde_Exception $e) {
                $desc = _("%s wishes to make you aware of \"%s\".");
                $is_update = false;

                // Check that you are one of the attendees here.
                if (!empty($attendees)) {
                    $identity = $injector->getInstance('IMP_Identity');
                    for ($i = 0, $c = count($attendees); $i < $c; ++$i) {
                        $attendee = parse_url($attendees[$i]);
                        if (!empty($attendee['path']) &&
                            $identity->hasAddress($attendee['path'])) {
                            $desc = _("%s requests your presence at \"%s\".");
                            break;
                        }
                    }
                }
            }

            if ($is_update && $registry->hasMethod('calendar/replace')) {
                $options['accept-import'] = _("Accept and update in my calendar");
                $options['import'] = _("Update in my calendar");
            } elseif ($registry->hasMethod('calendar/import')) {
                $options['accept-import'] = _("Accept and add to my calendar");
                $options['import'] = _("Add to my calendar");
            }

            $options['accept'] = _("Accept request");
            $options['tentative'] = _("Tentatively Accept request");
            $options['deny'] = _("Deny request");
            // $options['delegate'] = _("Delegate position");
            break;

        case 'ADD':
            $desc = _("%s wishes to amend \"%s\".");
            if ($registry->hasMethod('calendar/import')) {
                $options['import'] = _("Update this event on my calendar");
            }
            break;

        case 'REFRESH':
            $desc = _("%s wishes to receive the latest information about \"%s\".");
            $options['send'] = _("Send Latest Information");
            break;

        case 'REPLY':
            $desc = _("%s has replied to the invitation to \"%s\".");
            $sender = $this->getConfigParam('imp_contents')->getHeader()->getValue('From');
            if ($registry->hasMethod('calendar/updateAttendee') &&
                $this->_autoUpdateReply('auto_update_eventreply', $sender)) {
                try {
                    $registry->call('calendar/updateAttendee', array(
                        $vevent,
                        IMP::bareAddress($sender)
                    ));
                    $notification->push(_("Respondent Status Updated."), 'horde.success');
                } catch (Horde_Exception $e) {
                    $notification->push(sprintf(_("There was an error updating the event: %s"), $e->getMessage()), 'horde.error');
                }
            } else {
                $options['update'] = _("Update respondent status");
            }
            break;

        case 'CANCEL':
            try {
                $vevent->getAttribute('RECURRENCE-ID');
                $desc = _("%s has cancelled an instance of the recurring \"%s\".");
                if ($registry->hasMethod('calendar/replace')) {
                    $options['delete'] = _("Update in my calendar");
                }
            } catch (Horde_Icalendar_Exception $e) {
                $desc = _("%s has cancelled \"%s\".");
                if ($registry->hasMethod('calendar/delete')) {
                    $options['delete'] = _("Delete from my calendar");
                }
            }
            break;
        }

        $view = $this->_getViewOb();

        try {
            $start = $vevent->getAttribute('DTSTART');
            $view->start = is_array($start)
                ? strftime($prefs->getValue('date_format'), mktime(0, 0, 0, $start['month'], $start['mday'], $start['year']))
                : strftime($prefs->getValue('date_format'), $start) . ' ' . date($prefs->getValue('twentyFour') ? ' G:i' : ' g:i a', $start);
        } catch (Horde_Icalendar_Exception $e) {
            $start = null;
        }

        try {
            $end = $vevent->getAttribute('DTEND');
            $view->end = is_array($end)
                ? strftime($prefs->getValue('date_format'), mktime(0, 0, 0, $end['month'], $end['mday'], $end['year']))
                : strftime($prefs->getValue('date_format'), $end) . ' ' . date($prefs->getValue('twentyFour') ? ' G:i' : ' g:i a', $end);
        } catch (Horde_Icalendar_Exception $e) {
            $end = null;
        }

        try {
            $summary = $vevent->getAttribute('SUMMARY');
            $view->summary = $summary;
        } catch (Horde_Icalendar_Exception $e) {
            $summary = _("Unknown Meeting");
            $view->summary_error = _("None");
        }

        $view->desc = sprintf($desc, $sender, $summary);

        try {
            $view->desc2 = $vevent->getAttribute('DESCRIPTION');
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $view->loc = $vevent->getAttribute('LOCATION');
        } catch (Horde_Icalendar_Exception $e) {}

        if (!empty($attendees)) {
            $view->attendees = $this->_parseAttendees($vevent, $attendees);
        }

        if (!is_null($start) &&
            !is_null($end) &&
            in_array($method, array('PUBLISH', 'REQUEST', 'ADD')) &&
            $registry->hasMethod('calendar/getFbCalendars') &&
            $registry->hasMethod('calendar/listEvents')) {
            try {
                $calendars = $registry->call('calendar/getFbCalendars');

                $vevent_start = new Horde_Date($start);
                $vevent_end = new Horde_Date($end);

                // Check if it's an all-day event.
                if (is_array($start)) {
                    $vevent_allDay = true;
                    $vevent_end = $vevent_end->sub(1);
                } else {
                    $vevent_allDay = false;
                    $time_span_start = $vevent_start->sub($prefs->getValue('conflict_interval') * 60);
                    $time_span_end = $vevent_end->add($prefs->getValue('conflict_interval') * 60);
                }

                $events = $registry->call('calendar/listEvents', array($start, $vevent_end, $calendars, false));

                // TODO: Check if there are too many events to show.
                $conflicts = array();
                foreach ($events as $calendar) {
                    foreach ($calendar as $event) {
                        // TODO: WTF? Why are we using Kronolith constants
                        // here?
                        if (in_array($event->status, array(Kronolith::STATUS_CANCELLED, Kronolith::STATUS_FREE))) {
                            continue;
                        }

                        if ($vevent_allDay || $event->isAllDay()) {
                            $type = 'collision';
                        } elseif (($event->end->compareDateTime($time_span_start) <= -1) ||
                                ($event->start->compareDateTime($time_span_end) >= 1)) {
                            continue;
                        } elseif (($event->end->compareDateTime($vevent_start) <= -1) ||
                                  ($event->start->compareDateTime($vevent_end) >= 1)) {
                            $type = 'nearcollision';
                        } else {
                            $type = 'collision';
                        }

                        $conflicts[] = array(
                            'collision' => ($type == 'collision'),
                            'range' => $event->getTimeRange(),
                            'title' => $event->getTitle()
                        );
                    }
                }

                if (!empty($conflicts)) {
                    $view->conflicts = $conflicts;
                }
            } catch (Horde_Exception $e) {}
        }

        if (!empty($options)) {
            reset($options);
            $view->options = $options;
            $view->options_id = $id;
        }

        return $view->render('action');
    }

    /**
     * Generate the html for a vEvent.
     */
    protected function _vTodo($vtodo, $id, $method)
    {
        global $registry;

        $desc = '';
        $options = array();

        try {
            $organizer = $vtodo->getAttribute('ORGANIZER', true);
            if (isset($organizer[0]['CN'])) {
                $sender = $organizer[0]['CN'];
            } else {
                $organizer = parse_url($vtodo->getAttribute('ORGANIZER'));
                $sender = $organizer['path'];
            }
        } catch (Horde_Icalendar_Exception $e) {
            $sender = _("An unknown person");
        }

        switch ($method) {
        case 'PUBLISH':
            $desc = _("%s wishes to make you aware of \"%s\".");
            if ($registry->hasMethod('tasks/import')) {
                $options['import'] = _("Add this to my tasklist");
            }
            break;
        }

        $view = $this->_getViewOb();

        try {
            $view->priority = intval($vtodo->getAttribute('PRIORITY'));
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $summary = $view->summary = $vtodo->getAttribute('SUMMARY');
        } catch (Horde_Icalendar_Exception $e) {
            $summary = _("Unknown Task");
            $view->summary_error = _("None");
        }

        $view->desc = sprintf($desc, $sender, $summary);

        try {
            $view->desc2 = $vtodo->getAttribute('DESCRIPTION');
        } catch (Horde_Icalendar_Exception $e) {}

        try {
            $attendees = $vtodo->getAttribute('ATTENDEE');
        } catch (Horde_Icalendar_Exception $e) {
            $attendees = null;
        }

        if (!empty($attendees)) {
            if (!is_array($attendees)) {
                $attendees = array($attendees);
            }
            $view->attendees = $this->parseAttendees($vtodo, $attendees);
        }

        if (!empty($options)) {
            reset($options);
            $view->options = $options;
            $view->options_id = $id;
        }

        return $view->render('action');
    }

    /**
     * Translate the Participation status to string.
     *
     * @param string $value    The value of PARTSTAT.
     * @param string $default  The value to return as default.
     *
     * @return string   The translated string.
     */
    protected function _partstatToString($value, $default = null)
    {
        switch ($value) {
        case 'ACCEPTED':
            return _("Accepted");

        case 'DECLINED':
            return _("Declined");

        case 'TENTATIVE':
            return _("Tentatively Accepted");

        case 'DELEGATED':
            return _("Delegated");

        case 'COMPLETED':
            return _("Completed");

        case 'IN-PROCESS':
            return _("In Process");

        case 'NEEDS-ACTION':
        default:
            return is_null($default)
                ? _("Needs Action")
                : $default;
        }
    }

    /**
     * Get a Horde_View object.
     *
     * @return Horde_View  View object.
     */
    protected function _getViewOb()
    {
        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/itip'
        ));
        $view->addHelper('Text');

        return $view;
    }

    /**
     */
    protected function _parseAttendees($data, $attendees)
    {
        $params = $data->getAttribute('ATTENDEE', true);
        $tmp = array();

        foreach ($attendees as $key => $val) {
            if (!empty($params[$key]['CN'])) {
                $attendee = $params[$key]['CN'];
            } else {
                $val = parse_url($val);
                $attendee = empty($val['path'])
                    ? _("Unknown")
                    : $val['path'];
            }

            $role = _("Required Participant");
            if (isset($params[$key]['ROLE'])) {
                switch ($params[$key]['ROLE']) {
                case 'CHAIR':
                    $role = _("Chair Person");
                    break;

                case 'OPT-PARTICIPANT':
                    $role = _("Optional Participant");
                    break;

                case 'NON-PARTICIPANT':
                    $role = _("Non Participant");
                    break;

                case 'REQ-PARTICIPANT':
                default:
                    // Already set above.
                    break;
                }
            }

            $status = _("Awaiting Response");
            if (isset($params[$key]['PARTSTAT'])) {
                $status = $this->_partstatToString($params[$key]['PARTSTAT'], $status);
            }

            $tmp[] = array(
                'attendee' => $attendee,
                'role' => $role,
                'status' => $status
            );
        }

        return $tmp;
    }

    /**
     */
    protected function _autoUpdateReply($type, $sender)
    {
        if (!empty($this->_conf[$type])) {
            if (is_array($this->_conf[$type])) {
                $ob = new Horde_Mail_Rfc822_Address(IMP::bareAddress($sender));
                foreach ($this->_conf[$type] as $val) {
                    if ($ob->matchDomain($val)) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }

        return false;
    }

}
