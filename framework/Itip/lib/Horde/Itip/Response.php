<?php
/**
 * Handles Itip response data.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Handles Itip response data.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Itip
 * @author   Steffen Hansen <steffen@klaralvdalens-datakonsult.se>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
     * The status type of this response.
     *
     * @var Horde_Itip_Response_Type
     */
    private $_type;

    /**
     * Constructor.
     *
     * @param Horde_Itip_Event    $request  The request this
     *                                                     instance will respond
     *                                                     to.
     * @param Horde_Itip_Resource $resource The requested
     *                                                     resource.
     */
    public function __construct(
        Horde_Itip_Event $request,
        Horde_Itip_Resource $resource
    ) {
        $this->_request  = $request;
        $this->_resource = $resource;
    }

    /**
     * Return the response as an iCalendar vveEnt object.
     *
     * @param Horde_Itip_Response_Type $type The response type.
     * @param Horde_iCalendar|boolean  $vCal The parent container or false if not
     *                                       provided.
     *
     * @return Horde_iCalendar_vevent The response object.
     */
    public function getVevent(
        Horde_Itip_Response_Type $type,
        $vCal = false
    ) {
        $itip_reply = new Horde_Itip_Event_Vevent(
            Horde_iCalendar::newComponent('VEVENT', $vCal)
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
     * @param Horde_Itip_Response_Type $type       The response
     *                                                            type.
     * @param string                                  $product_id The ID that
     *                                                            should be set
     *                                                            as the iCalendar
     *                                                            product id.
     *
     * @return Horde_iCalendar The response object.
     */
    public function getIcalendar(
        Horde_Itip_Response_Type $type,
        $product_id
    ) {
        $vCal = new Horde_iCalendar();
        $vCal->setAttribute('PRODID', $product_id);
        $vCal->setAttribute('METHOD', 'REPLY');
        $vCal->addComponent($this->getVevent($type, $vCal));
        return $vCal;
    }

    /**
     * Return the response as a MIME message.
     *
     * @param Horde_Itip_Response_Type $type       The response
     *                                                            type.
     * @param string                                  $product_id The ID that
     *                                                            should be set
     *                                                            as the iCalendar
     *                                                            product id.
     * @param string                                  $subject_comment An optional comment on the subject line.
     *
     * @return array A list of two object: The mime headers and the mime
     *               message.
     */
    public function getMessage(
        Horde_Itip_Response_Type $type,
        $product_id,
        $subject_comment = null
    ) {
        $ics = new MIME_Part(
            'text/calendar',
            $this->getIcalendar($type, $product_id)->exportvCalendar(),
            'UTF-8'
        );
        $ics->setContentTypeParameter('method', 'REPLY');

        //$mime->addPart($body);
        //$mime->addPart($ics);
        // The following was ::convertMimePart($mime). This was removed so that we
        // send out single-part MIME replies that have the iTip file as the body,
        // with the correct mime-type header set, etc. The reason we want to do this
        // is so that Outlook interprets the messages as it does Outlook-generated
        // responses, i.e. double-clicking a reply will automatically update your
        // meetings, showing different status icons in the UI, etc.
        $message = MIME_Message::convertMimePart($ics);
        $message->setCharset('UTF-8');
        $message->setTransferEncoding('quoted-printable');
        $message->transferEncodeContents();

        // Build the reply headers.
        $headers = new MIME_Headers();
        $headers->addHeader('Date', date('r'));
        $headers->addHeader('From', $this->_resource->getFrom());
        $headers->addHeader('To', $this->_request->getOrganizer());
        $headers->addHeader(
            'Subject', $type->getSubject($subject_comment)
        );
        $headers->addMIMEHeaders($message);
        return array($headers, $message);
    }
}