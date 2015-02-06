<?php
/**
 * Handles Itip response data for vTodo.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Handles Itip response data for vTodo.
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.horde.org/licenses/lgpl21 LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @author   Michael J Rubinsky <mrubinsk@horde.org>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 *
 * @todo For H6, look at protected/private visibility of parent class' methods
 *       and properties. Needed to provide duplicated methods, like __construct
 *       due to private members. Might be able to combine classes once type
 *       hints are fixed/changed in the Horde_Itip_Event_* classes.
 */
class Horde_Itip_Response_Vtodo extends Horde_Itip_Response
{
    /**
     * The request we are going to answer.
     *
     * @var Horde_Itip_Event_Vtodo
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
     * Return the response as an iCalendar vTodo object.
     *
     * @param Horde_Itip_Response_Type $type The response type.
     * @param Horde_Icalendar|boolean  $vCal The parent container or false if not
     *                                       provided.
     *
     * @return Horde_Icalendar_Vtodo The response object.
     * @todo Refactor this along with parent class. This method name is confusing,
     *       but necessary due to the parent class' method name. It returns
     *       a vTodo, not vEvent.
     */
    public function getVevent(
        Horde_Itip_Response_Type $type,
        $vCal = false
    )
    {
        $itip_reply = new Horde_Itip_Event_Vtodo(
            Horde_Icalendar::newComponent('VTODO', $vCal)
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
     * Return the response as a MIME message.
     *
     * @param Horde_Itip_Response_Type    $type    The response type.
     * @param Horde_Itip_Response_Options $options The options for the response.
     *
     * @return array A list of two object: The mime headers and the mime
     *               message.
     * @todo  I tried to abstract just the .ics filename, but due to private
     * members in the parent class, must override the entire method here.
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
        $message->setEOL("\r\n");
        $this->_setIcsFilename($message);
        $message->setContentTypeParameter('METHOD', 'REPLY');

        // Build the reply headers.
        $from = $this->_resource->getFrom();
        $reply_to = $this->_resource->getReplyTo();
        $headers = new Horde_Mime_Headers();
        $headers->addHeaderOb(Horde_Mime_Headers_Date::create());
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

    protected function _setIcsFilename(Horde_Mime_Part &$message)
    {
        $message->setName('task-reply.ics');
    }

}