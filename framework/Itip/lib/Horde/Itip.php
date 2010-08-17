<?php
/**
 * Handles iTip invitation requests/responses.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Handles iTip invitation requests/responses.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
class Horde_Itip
{
    /**
     * The iTip response.
     *
     * @var Horde_Itip_Response
     */
    private $_response;

    /**
     * Constructor.
     *
     * @param Horde_Itip_Response $response The iTip response.
     */
    public function __construct(Horde_Itip_Response $response)
    {
        $this->_response = $response;
    }

    /**
     * Return the response as an iCalendar vEvent object.
     *
     * @param Horde_Itip_Response_Type $type The response type.
     *
     * @return Horde_Icalendar_Vevent The response object.
     */
    public function getVeventResponse(
        Horde_Itip_Response_Type $type
    ) {
        return $this->_response->getVevent(
            $type, false
        );
    }

    /**
     * Return the response as an iCalendar object.
     *
     * @param Horde_Itip_Response_Type $type       The response type.
     * @param string                   $product_id The ID that should be set as
     *                                             the iCalendar product id.
     *
     * @return Horde_iCalendar The response object.
     */
    public function getIcalendarResponse(
        Horde_Itip_Response_Type $type,
        $product_id
    ) {
        return $this->_response->getIcalendar(
            $type, $product_id
        );
    }

    /**
     * Send the response as a single part MIME message.
     *
     * @param Horde_Itip_Response_Type    $type      The response type.
     * @param Horde_Itip_Response_Options $options   The options for the response.
     * @param Horde_Mail_Transport        $transport The mail transport.
     *
     * @return array A list of two object: The mime headers and the mime
     *               message.
     */
    public function sendSinglepartResponse(
        Horde_Itip_Response_Type $type,
        Horde_Itip_Response_Options $options,
        Horde_Mail_Transport $transport
    ) {
        list($headers, $body) = $this->_response->getMessage(
            $type, $options
        );
        $body->send(
            $this->_response->getRequest()->getOrganizer(),
            $headers,
            $transport
        );
    }

    /**
     * Send the invitation response as a multi part MIME message.
     *
     * @param Horde_Itip_Response_Type    $type      The response type.
     * @param Horde_Itip_Response_Options $options   The options for the response.
     * @param Horde_Mail_Transport        $transport The mail transport.
     *
     * @return NULL
     */
    public function sendMultipartResponse(
        Horde_Itip_Response_Type $type,
        Horde_Itip_Response_Options $options,
        Horde_Mail_Transport $transport
    ) {
        list($headers, $body) = $this->_response->getMultiPartMessage(
            $type, $options
        );
        $body->send(
            $this->_response->getRequest()->getOrganizer(),
            $headers,
            $transport
        );
    }

    /**
     * Factory for generating a response object for an iCalendar invitation.
     *
     * @param Horde_Icalendar_Vevent $vevent   The iCalendar request.
     * @param Horde_Itip_Resource    $resource The invited resource.
     *
     * @return Horde_Itip_Response The prepared response.
     */
    static public function prepareResponse(
        Horde_Icalendar_Vevent $vevent,
        Horde_Itip_Resource $resource
    ) {
        return new Horde_Itip_Response(
            new Horde_Itip_Event_Vevent(
                $vevent
            ),
            $resource
        );
    }

    /**
     * Factory for generating an iTip handler for an iCalendar invitation.
     *
     * @param Horde_Icalendar_Vevent $vevent   The iCalendar request.
     * @param Horde_Itip_Resource    $resource The invited resource.
     *
     * @return Horde_Itip The iTip handler.
     */
    static public function factory(
        Horde_Icalendar_Vevent $vevent,
        Horde_Itip_Resource $resource
    ) {
        return new Horde_Itip(
            self::prepareResponse($vevent, $resource)
        );
    }
}