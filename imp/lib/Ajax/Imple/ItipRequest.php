<?php
/**
 * Attach javascript used to process Itip actions into a page.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Imple_ItipRequest extends Horde_Core_Ajax_Imple
{
    /**
     * Request DOM ID counter.
     *
     * @var integer
     */
    static protected $_requestId = 0;

    /**
     * Constructor.
     *
     * @param array $params  Configuration parameters:
     *   - id: (string) [OPTIONAL] The DOM ID to attach to.
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - mime_id: (string) The MIME ID of the message part with the key.
     *   - uid: (string) The UID of the message.
     */
    public function __construct($params)
    {
        if (!isset($params['id'])) {
            $params['id'] = 'imp_itiprequest' . self::$_requestId;
        }

        ++self::$_requestId;

        parent::__construct($params);
    }

    /**
     * Attach the object to a javascript event.
     */
    public function attach()
    {
        if (self::$_requestId == 1) {
            Horde::addScriptFile('itiprequest.js', 'imp');
        }

        Horde::addInlineJsVars(array(
            'IMPItipRequest.handles[' . Horde_Serialize::serialize($this->getRequestId(), Horde_Serialize::JSON) . ']' => true
        ), array('onload' => 'dom'));
    }

    /**
     * Perform the given action.
     *
     * Variables required in form input:
     *   - identity (TODO: ? Code uses it, but it is never set anywhere)
     *   - itip_action
     *   - mailbox
     *   - mime_id
     *   - uid
     *
     * @param array $args  Not used.
     * @param array $post  Not used.
     *
     * @return object  An object with the following response entries:
     *   - success: (integer) 1 on success, 0 on failure.
     */
    public function handle($args, $post)
    {
        global $conf, $injector, $notification, $registry;

        $vars = Horde_Variables::getDefaultVariables();

        $actions = $vars->get('itip_action', array());
        $result = 0;
        $vCal = new Horde_Icalendar();

        /* Retrieve the calendar data from the message. */
        try {
            $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices(IMP_Mailbox::formFrom($vars->mailbox), $vars->uid));
            $mime_part = $contents->getMIMEPart($vars->mime_id);
            if (empty($mime_part)) {
                throw new IMP_Exception(_("Cannot retrieve calendar data from message."));
            } elseif (!$vCal->parsevCalendar($mime_part->getContents(), 'VCALENDAR', $mime_part->getCharset())) {
                throw new IMP_Exception(_("The calendar data is invalid"));
            }

            $components = $vCal->getComponents();
        } catch (Exception $e) {
            $notification->push($e, 'horde.error');
            $actions = array();
        }

        foreach ($actions as $key => $action) {
            switch ($action) {
            case 'delete':
                // vEvent cancellation.
                if ($registry->hasMethod('calendar/delete')) {
                    $guid = $components[$key]->getAttribute('UID');
                    $recurrenceId = null;

                    try {
                        // This is a cancellation of a recurring event instance.
                        $recurrenceId = $components[$key]->getAttribute('RECURRENCE-ID');
                    } catch (Horde_Icalendar_Exception $e) {}

                    try {
                        $registry->call('calendar/delete', array($guid, $recurrenceId));
                        $notification->push(_("Event successfully deleted."), 'horde.success');
                        $result = 1;
                    } catch (Horde_Exception $e) {
                        $notification->push(sprintf(_("There was an error deleting the event: %s"), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $notification->push(_("This action is not supported."), 'horde.warning');
                }
                break;

            case 'update':
                // vEvent reply.
                if ($registry->hasMethod('calendar/updateAttendee')) {
                    try {
                        $registry->call('calendar/updateAttendee', array(
                            $components[$key],
                            IMP::bareAddress($contents->getHeader()->getValue('From'))
                        ));
                        $notification->push(_("Respondent Status Updated."), 'horde.success');
                        $result = 1;
                    } catch (Horde_Exception $e) {
                        $notification->push(sprintf(_("There was an error updating the event: %s"), $e->getMessage()), 'horde.error');
                    }
                } else {
                    $notification->push(_("This action is not supported."), 'horde.warning');
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
                    $guid = $components[$key]->getAttribute('UID');

                    // Check if this is an update.
                    try {
                        $registry->call('calendar/export', array($guid, 'text/calendar'));
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
                                    Horde::img('mime/icalendar.png', _("View event")) .
                                    '</a>',
                                'horde.success',
                                array('content.raw')
                            );
                            $result = 1;
                            break;
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
                                    Horde::img('mime/icalendar.png', _("View event")) .
                                    '</a>',
                                'horde.success',
                                array('content.raw')
                            );
                            $result = 1;
                            break;
                        } catch (Horde_Exception $e) {
                            $notification->push(sprintf(_("There was an error importing the event: %s"), $e->getMessage()), 'horde.error');
                        }
                    }

                    $notification->push(_("This action is not supported."), 'horde.warning');
                    break;

                case 'vFreebusy':
                    // Import into Kronolith.
                    if ($registry->hasMethod('calendar/import_vfreebusy')) {
                        try {
                            $registry->call('calendar/import_vfreebusy', array($components[$key]));
                            $notification->push(_("The user's free/busy information was sucessfully stored."), 'horde.success');
                            $result = 1;
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
                                    Horde::img('mime/icalendar.png', _("View task")) .
                                    '</a>',
                                'horde.success',
                                array('content.raw')
                            );
                            $result = 1;
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
                    ($components[$key]->getType() == 'vEvent')) {
                    $vEvent = $components[$key];

                    $resource = new Horde_Itip_Resource_Identity(
                        $injector->getInstance('IMP_Identity'),
                        $vEvent->getAttribute('ATTENDEE'),
                        $vars->identity
                    );

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
                        // Send the reply.
                        Horde_Itip::factory($vEvent, $resource)->sendMultiPartResponse(
                            $type,
                            new Horde_Itip_Response_Options_Horde(
                                'UTF-8',
                                array(
                                    'dns' => $injector->getInstance('Net_DNS2_Resolver'),
                                    'server' => $conf['server']['name']
                                )
                            ),
                            $injector->getInstance('IMP_Mail')
                        );
                        $notification->push(_("Reply Sent."), 'horde.success');
                        $result = 1;
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
                    $organizerName = isset($organizer['cn'])
                        ? $organizer['cn']
                        : '';

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

                    // Build the reply.
                    $msg_headers = new Horde_Mime_Headers();

                    $vCal = new Horde_Icalendar();
                    $vCal->setAttribute('PRODID', '-//The Horde Project//' . $msg_headers->getUserAgent() . '//EN');
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
                    $msg_headers->addReceivedHeader(array(
                        'dns' => $injector->getInstance('Net_DNS2_Resolver'),
                        'server' => $conf['server']['name']
                    ));
                    $msg_headers->addMessageIdHeader();
                    $msg_headers->addHeader('Date', date('r'));
                    $msg_headers->addHeader('From', $email);
                    $msg_headers->addHeader('To', $organizerEmail);

                    $identity->setDefault($vars->identity);
                    $replyto = $identity->getValue('replyto_addr');
                    if (!empty($replyto) && ($replyto != $email)) {
                        $msg_headers->addHeader('Reply-To', $replyto);
                    }
                    $msg_headers->addHeader('Subject', _("Free/Busy Request Response"));

                    // Send the reply.
                    try {
                        $mime->send($organizerEmail, $msg_headers, $injector->getInstance('IMP_Mail'));
                        $notification->push(_("Reply Sent."), 'horde.success');
                        $result = 1;
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

        return new Horde_Core_Ajax_Response($result, true);
    }

    /**
     * Generates a unique DOM ID.
     *
     * @return string  A unique DOM ID.
     */
    public function getRequestId()
    {
        return $this->_params['id'];
    }

    /**
     * Get the form URL for the request.
     *
     * @return Horde_Url  Form URL.
     */
    public function getFormUrl()
    {
        $js_params = array(
            'mailbox' => $this->_params['mailbox']->form_to,
            'mime_id' => $this->_params['mime_id'],
            'uid' => $this->_params['uid']
        );

        if (defined('SID')) {
            parse_str(SID, $sid);
            $js_params = array_merge($js_params, $sid);
        }

        return $this->_getUrl('ItipRequest', 'imp', array('sessionWrite' => 1))->add($js_params);
    }

}
