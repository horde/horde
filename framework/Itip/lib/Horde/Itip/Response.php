<?php
/**
 * Handles Itip response data.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Handles Itip response data.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip_Response
{
    /**
     * The request we are going to answer.
     *
     * @var Horde_Itip_Event
     */
    private $_request;

    /**
     * The requested resource.
     *
     * @var Horde_Itip_Resource
     */
    private $_resource;

    /**
     * Constructor.
     *
     * @param Horde_Itip_Event    $request  The request this instance will
     *                                      respond to.
     * @param Horde_Itip_Resource $resource The requested resource.
     */
    public function __construct(
        Horde_Itip_Event $request,
        Horde_Itip_Resource $resource
    )
    {
        $this->_request  = $request;
        $this->_resource = $resource;
    }

    /**
     * Return the original request.
     *
     * @return Horde_Itip_Event The original request.
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Return the response as an iCalendar vEvent object.
     *
     * @param Horde_Itip_Response_Type $type The response type.
     * @param Horde_iCalendar|boolean  $vCal The parent container or false if not
     *                                       provided.
     *
     * @return Horde_Icalendar_Vevent The response object.
     */
    public function getVevent(
        Horde_Itip_Response_Type $type,
        $vCal = false
    )
    {
        $itip_reply = new Horde_Itip_Event_Vevent(
            Horde_Icalendar::newComponent('VEVENT', $vCal)
        );
        $this->_request->copyEventInto($itip_reply);

        $type->setRequest($this->_request);

        $itip_reply->setAttendee(
            $this->_resource->getMailAddress(),
            $this->_resource->getCommonName(),
            $type->getStatus()
        );
        return $itip_reply->getVevent();
    }

    /**
     * Return the response as an iCalendar object.
     *
     * @param Horde_Itip_Response_Type $type       The response type.
     * @param Horde_Itip_Response_Options $options The options for the response.
     *
     * @return Horde_Icalendar The response object.
     */
    public function getIcalendar(
        Horde_Itip_Response_Type $type,
        Horde_Itip_Response_Options $options
    )
    {
        $vCal = new Horde_Icalendar();
        $options->prepareIcalendar($vCal);
        $vCal->setAttribute('METHOD', 'REPLY');
        $vCal->addComponent($this->getVevent($type, $vCal));
        return $vCal;
    }

    /**
     * Return the response as a MIME message.
     *
     * @param Horde_Itip_Response_Type    $type    The response type.
     * @param Horde_Itip_Response_Options $options The options for the response.
     *
     * @return array A list of two object: The mime headers and the mime
     *               message.
     */
    public function getMessage(
        Horde_Itip_Response_Type $type,
        Horde_Itip_Response_Options $options
    )
    {
        $message = new Horde_Mime_Part();
        $message->setType('text/calendar');
        $options->prepareIcsMimePart($message);
        $message->setContents(
            $this->getIcalendar($type, $options)->exportvCalendar()
        );
        $message->setName('event-reply.ics');
        $message->setContentTypeParameter('METHOD', 'REPLY');

        // Build the reply headers.
        $from = $this->_resource->getFrom();
        $reply_to = $this->_resource->getReplyTo();
        $headers = new Horde_Mime_Headers();
        $headers->addHeader('Date', date('r'));
        $headers->addHeader('From', $from);
        $headers->addHeader('To', $this->_request->getOrganizer());
        if (!empty($reply_to) && $reply_to != $from) {
            $headers->addHeader('Reply-to', $reply_to);
        }
        $headers->addHeader(
            'Subject', $type->getSubject()
        );

        $options->prepareResponseMimeHeaders($headers);

        return array($headers, $message);
    }

    /**
     * Return the response as a MIME message.
     *
     * @param Horde_Itip_Response_Type $type       The response type.
     * @param Horde_Itip_Response_Options $options The options for the response.
     *
     * @return array A list of two object: The mime headers and the mime
     *               message.
     */
    public function getMultiPartMessage(
        Horde_Itip_Response_Type $type,
        Horde_Itip_Response_Options $options
    )
    {
        $message = new Horde_Mime_Part();
        $message->setType('multipart/alternative');

        list($headers, $ics) = $this->getMessage($type, $options);

        $body = new Horde_Mime_Part();
        $body->setType('text/plain');
        $options->prepareMessageMimePart($body);
        $body->setContents(Horde_String::wrap($type->getMessage(), 76, "\n"));

        $message->addPart($body);
        $message->addPart($ics);

        return array($headers, $message);
    }
}