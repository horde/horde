<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Attach javascript used to process Itip actions into a page.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Imple_ItipRequest extends Horde_Core_Ajax_Imple
{
    /**
     */
    protected $_observe = 'submit';

    /**
     * @param array $params  Configuration parameters:
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - muid: (string) MUID of the message.
     */
    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }

    /**
     */
    protected function _attach($init)
    {
        return array(
            'mime_id' => $this->_params['mime_id'],
            'muid' => $this->_params['muid']
        );
    }

    /**
     * Variables required in form input:
     *   - identity (TODO: ? Code uses it, but it is never set anywhere)
     *   - imple_submit: itip_action(s)
     *   - mime_id
     *   - muid
     *
     * @return boolean  True on success.
     */
    protected function _handle(Horde_Variables $vars)
    {
        global $injector, $notification, $registry;

        $actions = (array)$vars->imple_submit;
        $result = false;
        $vCal = new Horde_Icalendar();

        /* Retrieve the calendar data from the message. */
        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices_Mailbox($vars));
            if (!($mime_part = $contents->getMimePart($vars->mime_id))) {
                throw new IMP_Exception(
                    _("Cannot retrieve calendar data from message.")
                );
            } elseif (!$vCal->parsevCalendar($mime_part->getContents(), 'VCALENDAR', $mime_part->getCharset())) {
                throw new IMP_Exception(_("The calendar data is invalid"));
            }

            $components = $vCal->getComponents();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            $actions = array();
        }

        foreach ($actions as $key => $action) {
            $pos = strpos($key, '[');
            $key = substr($key, $pos + 1, strlen($key) - $pos - 2);

            switch ($action) {
            case 'delete':
                // vEvent cancellation.
                if ($registry->hasMethod('calendar/delete')) {
                    $guid = $components[$key]->getAttribute('UID');
                    $recurrenceId = null;
                    try {
                        // This is a cancellation of a recurring event instance.
                        $recurrenceId = $components[$key]->getAttribute('RECURRENCE-ID');
                        $atts = $components[$key]->getAttribute('RECURRENCE-ID', true);
                        $range = null;
                        foreach ($atts as $att) {
                            if (array_key_exists('RANGE', $att)) {
                                $range = $att['RANGE'];
                            }
                        }
                    } catch (Horde_Icalendar_Exception $e) {}

                    try {
                        $registry->call('calendar/delete', array($guid, $recurrenceId, $range));
                        $notification->push(_("Event successfully deleted."), 'horde.success');
                        $result = true;
                    } catch (Horde_Exception $e) {
                        $notification->push(sprintf(_("There was an error deleting the event: %s"), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $notification->push(_("This action is not supported."), 'horde.warning');
                }
                break;

            case 'update':
                // vEvent reply.
                // vTodo reply.
                switch ($components[$key]->getType()) {
                case 'vEvent':
                    if ($registry->hasMethod('calendar/updateAttendee')) {
                        try {
                            if ($tmp = $contents->getHeader()->getHeader('from')) {
                                $registry->call('calendar/updateAttendee', array(
                                    $components[$key],
                                    $tmp->getAddressList(true)->first()->bare_address
                                ));
                                $notification->push(_("Respondent Status Updated."), 'horde.success');
                                $result = true;
                            }
                        } catch (Horde_Exception $e) {
                            $notification->push(sprintf(_("There was an error updating the event: %s"), $e->getMessage()), 'horde.error');
                        }
                    } else {
                        $notification->push(_("This action is not supported."), 'horde.warning');
                    }
                    break;
                case 'vTodo':
                    if ($registry->hasMethod('tasks/updateAttendee')) {
                        try {
                            if ($tmp = $contents->getHeader()->getHeader('from')) {
                                $registry->call('tasks/updateAttendee', array(
                                    $components[$key],
                                    $tmp->getAddressList(true)->first()->bare_address
                                ));
                                $notification->push(_("Respondent Status Updated."), 'horde.success');
                                $result = true;
                            }
                        } catch (Horde_Exception $e) {
                            $notification->push(sprintf(_("There was an error updating the task: %s"), $e->getMessage()), 'horde.error');
                        }
                    } else {
                        $notification->push(_("This action is not supported."), 'horde.warning');
                    }
                    break;
                }
                break;

            case 'import':
            case 'accept-import':
                // vFreebusy reply.
                // vFreebusy publish.
                // vEvent request.
                // vEvent publish.
                // vTodo publish.
                // vJournal publish.
                switch ($components[$key]->getType()) {
                case 'vEvent':
                    $result = $this->_handlevEvent($key, $components, $mime_part);
                    // Must check for exceptions.
                    foreach ($components as $k => $component) {
                        try {
                            if ($component->getType() == 'vEvent' && $component->getAttribute('RECURRENCE-ID')) {
                                $uid = $component->getAttribute('UID');
                                if ($uid == $components[$key]->getAttribute('UID')) {
                                    $this->_handlevEvent($k, $components, $mime_part);
                                }
                            }
                        } catch (Horde_Icalendar_Exception $e) {}
                    }

                    break;
                case 'vFreebusy':
                    // Import into Kronolith.
                    if ($registry->hasMethod('calendar/import_vfreebusy')) {
                        try {
                            $registry->call('calendar/import_vfreebusy', array($components[$key]));
                            $notification->push(_("The user's free/busy information was sucessfully stored."), 'horde.success');
                            $result = true;
                        } catch (Horde_Exception $e) {
                            $notification->push(sprintf(_("There was an error importing user's free/busy information: %s"), $e->getMessage()), 'horde.error');
                        }
                    } else {
                        $notification->push(_("This action is not supported."), 'horde.warning');
                    }
                    break;

                case 'vTodo':
                    // Import into Nag.
                    if ($registry->hasMethod('tasks/import')) {
                        try {
                            $guid = $registry->call('tasks/import', array(
                                $components[$key],
                                $mime_part->getType()
                            ));
                            $url = Horde::url($registry->link('tasks/show', array('uid' => $guid)));
                            $notification->push(
                                _("The task has been added to your tasklist.") . '&nbsp;' .
                                    Horde::link($url, _("View task"), null, '_blank') .
                                    Horde_Themes_Image::tag('mime/icalendar.png', array('alt' => _("View task"))) .
                                    '</a>',
                                'horde.success',
                                array('content.raw')
                            );
                            $result = true;
                        } catch (Horde_Exception $e) {
                            $notification->push(sprintf(_("There was an error importing the task: %s"), $e->getMessage()), 'horde.error');
                        }
                    } else {
                        $notification->push(_("This action is not supported."), 'horde.warning');
                    }
                    break;

                case 'vJournal':
                default:
                    $notification->push(_("This action is not supported."), 'horde.warning');
                }

                if ($action == 'import') {
                    break;
                }
                // Fall-through for 'accept-import'

            case 'accept':
            case 'deny':
            case 'tentative':
                // vEvent request.
                if (isset($components[$key]) &&
                    ($components[$key]->getType() == 'vEvent' ||
                     $components[$key]->getType() == 'vTodo')) {
                    $vEvent = $components[$key];

                    try {
                        $resource = new Horde_Itip_Resource_Identity(
                            $injector->getInstance('IMP_Identity'),
                            $vEvent->getAttribute('ATTENDEE'),
                            $vars->identity
                        );
                    } catch (Horde_Icalendar_Exception $e) {
                        throw new Horde_Itip_Exception('No ATTENDEE data, unable to reply.');
                    }

                    switch ($action) {
                    case 'accept':
                    case 'accept-import':
                        $type = new Horde_Itip_Response_Type_Accept($resource);
                        break;

                    case 'deny':
                        $type = new Horde_Itip_Response_Type_Decline($resource);
                        break;

                    case 'tentative':
                        $type = new Horde_Itip_Response_Type_Tentative($resource);
                        break;
                    }

                    try {
                        if ($vEvent->getType() == 'vEvent') {
                            // Send the reply.
                            Horde_Itip::factory($vEvent, $resource)->sendMultiPartResponse(
                                $type,
                                new Horde_Core_Itip_Response_Options_Horde('UTF-8', array()),
                                $injector->getInstance('IMP_Mail')
                            );
                        } elseif ($vEvent->getType() == 'vTodo') {
                            Horde_Itip::vTodoFactory($vEvent, $resource)->sendMultiPartResponse(
                                $type,
                                new Horde_Core_Itip_Response_Options_Horde('UTF-8', array()),
                                $injector->getInstance('IMP_Mail')
                            );
                        }
                        $notification->push(_("Reply Sent."), 'horde.success');
                        $result = true;
                    } catch (Horde_Itip_Exception $e) {
                        $notification->push(sprintf(_("Error sending reply: %s."), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $notification->push(_("This action is not supported."), 'horde.warning');
                }
                break;

            case 'send':
            case 'reply':
            case 'reply2m':
                // vfreebusy request.
                if (isset($components[$key]) &&
                    ($components[$key]->getType() == 'vFreebusy')) {
                    $vFb = $components[$key];

                    // Get the organizer details.
                    try {
                        $organizer = parse_url($vFb->getAttribute('ORGANIZER'));
                    } catch (Horde_Icalendar_Exception $e) {
                        break;
                    }

                    $organizerEmail = $organizer['path'];

                    $organizer = $vFb->getAttribute('ORGANIZER', true);
                    $organizerFullEmail = new Horde_Mail_Rfc822_Address($organizerEmail);
                    if (isset($organizer['cn'])) {
                        $organizerFullEmail->personal = $organizer['cn'];
                    }

                    if ($action == 'reply2m') {
                        $startStamp = time();
                        $endStamp = $startStamp + (60 * 24 * 3600);
                    } else {
                        try {
                            $startStamp = $vFb->getAttribute('DTSTART');
                        } catch (Horde_Icalendar_Exception $e) {
                            $startStamp = time();
                        }

                        try {
                            $endStamp = $vFb->getAttribute('DTEND');
                        } catch (Horde_Icalendar_Exception $e) {}

                        if (!$endStamp) {
                            try {
                                $duration = $vFb->getAttribute('DURATION');
                                $endStamp = $startStamp + $duration;
                            } catch (Horde_Icalendar_Exception $e) {
                                $endStamp = $startStamp + (60 * 24 * 3600);
                            }
                        }
                    }

                    $vfb_reply = $registry->call('calendar/getFreeBusy', array(
                        $startStamp,
                        $endStamp
                    ));

                    // Find out who we are and update status.
                    $identity = $injector->getInstance('IMP_Identity');
                    $email = $identity->getFromAddress();

                    $vCal = new Horde_Icalendar();
                    $vCal->setAttribute('PRODID', '-//The Horde Project//' . strval(Horde_Mime_Headers_UserAgent::create()) . '//EN');
                    $vCal->setAttribute('METHOD', 'REPLY');
                    $vCal->addComponent($vfb_reply);

                    $message = _("Attached is a reply to a calendar request you sent.");
                    $body = new Horde_Mime_Part();
                    $body->setType('text/plain');
                    $body->setCharset('UTF-8');
                    $body->setContents(Horde_String::wrap($message, 76));

                    $ics = new Horde_Mime_Part();
                    $ics->setType('text/calendar');
                    $ics->setCharset('UTF-8');
                    $ics->setContents($vCal->exportvCalendar());
                    $ics->setName('icalendar.ics');
                    $ics->setContentTypeParameter('METHOD', 'REPLY');

                    $mime = new Horde_Mime_Part();
                    $mime->addPart($body);
                    $mime->addPart($ics);

                    // Build the reply headers.
                    $msg_headers = new Horde_Mime_Headers();
                    $msg_headers->addHeaderOb(
                        Horde_Core_Mime_Headers_Received::createHordeHop()
                    );
                    $msg_headers->addHeaderOb(
                        Horde_Mime_Headers_MessageId::create()
                    );
                    $msg_headers->addHeaderOb(
                        Horde_Mime_Headers_Date::create()
                    );
                    $msg_headers->addHeader('From', $email);
                    $msg_headers->addHeader('To', $organizerFullEmail);

                    $identity->setDefault($vars->identity);
                    $replyto = $identity->getValue('replyto_addr');
                    if (!empty($replyto) && !$email->match($replyto)) {
                        $msg_headers->addHeader('Reply-To', $replyto);
                    }
                    $msg_headers->addHeader('Subject', _("Free/Busy Request Response"));

                    // Send the reply.
                    try {
                        $mime->send($organizerEmail, $msg_headers, $injector->getInstance('IMP_Mail'));
                        $notification->push(_("Reply Sent."), 'horde.success');
                        $result = true;
                    } catch (Exception $e) {
                        $notification->push(sprintf(_("Error sending reply: %s."), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $notification->push(_("Invalid Action selected for this component."), 'horde.warning');
                }
                break;

            case 'nosup':
                // vFreebusy request.
            default:
                $notification->push(_("This action is not supported."), 'horde.warning');
                break;
            }
        }

        return $result;
    }

    protected function _handlevEvent($key, array $components, $mime_part)
    {
        global $notification, $registry;

        try {
            $guid = $components[$key]->getAttribute('UID');
        } catch (Horde_Icalendar_Exception $e) {
            /* If required UID parameter doesn't exist, make one
             * up so the user can at least add the event to the
             * calendar. */
            $guid = strval(new Horde_Support_Guid());
        }

        // Check if this is an update.
        try {
            $calendars = $registry->calendar->listCalendars(true);
            $registry->call('calendar/export', array($guid, 'text/calendar', array(), $calendars));
            $success = true;
        } catch (Horde_Exception $e) {
            $success = false;
        }

        // Try to update in calendar.
        if ($success && $registry->hasMethod('calendar/replace')) {
            try {
                $registry->call('calendar/replace', array(
                    $guid,
                    $components[$key],
                    $mime_part->getType()
                ));
                $url = Horde::url($registry->link('calendar/show', array('uid' => $guid)));
                $notification->push(
                    _("The event was updated in your calendar.") . '&nbsp;' .
                        Horde::link($url, _("View event"), null, '_blank') .
                        Horde_Themes_Image::tag('mime/icalendar.png', array('alt' => _("View event"))) .
                        '</a>',
                    'horde.success',
                    array('content.raw')
                );
                return true;
            } catch (Horde_Exception $e) {
                // Could be a missing permission.
                $notification->push(sprintf(_("There was an error updating the event: %s Trying to import the event instead."), $e->getMessage()), 'horde.warning');
            }
        }

        if ($registry->hasMethod('calendar/import')) {
            // Import into calendar.
            try {
                $guid = $registry->call('calendar/import', array(
                    $components[$key],
                    $mime_part->getType()
                ));
                $url = Horde::url($registry->link('calendar/show', array('uid' => $guid)));
                $notification->push(
                    _("The event was added to your calendar.") . '&nbsp;' .
                        Horde::link($url, _("View event"), null, '_blank') .
                        Horde_Themes_Image::tag('mime/icalendar.png', array('alt' => _("View event"))) .
                        '</a>',
                    'horde.success',
                    array('content.raw')
                );
                return true;

            } catch (Horde_Exception $e) {
                $notification->push(sprintf(_("There was an error importing the event: %s"), $e->getMessage()), 'horde.error');
            }
        }

        $notification->push(_("This action is not supported."), 'horde.warning');

        return false;
    }

}
