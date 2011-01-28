<?php
/**
 * Holds iTip response options.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Holds iTip response options.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
interface Horde_Itip_Response_Options
{
    /**
     * Prepare the iCalendar part of the response object.
     *
     * @param Horde_Icalendar $ical The iCalendar response object.
     *
     * @return NULL
     */
    public function prepareIcalendar(Horde_Icalendar $ical);

    /**
     * Prepare the iCalendar MIME part of the response message.
     *
     * @param Horde_Mime_Part $ics The iCalendar MIME part of the response
     *                             message.
     *
     * @return NULL
     */
    public function prepareResponseMimeHeaders(Horde_Mime_Headers $headers);

    /**
     * Prepare the iCalendar MIME part of the response message.
     *
     * @param Horde_Mime_Part $ics The iCalendar MIME part of the response
     *                             message.
     *
     * @return NULL
     */
    public function prepareIcsMimePart(Horde_Mime_Part $ics);

    /**
     * Prepare the message MIME part of the response.
     *
     * @param Horde_Mime_Part $message The message MIME part of the response.
     *
     * @return NULL
     */
    public function prepareMessageMimePart(Horde_Mime_Part $message);

    /**
     * Get the character set for the response mime parts.
     *
     * @return string The character set.
     */
    public function getCharacterSet();

    /**
     * Get the product ID of the iCalendar object embedded in the MIME response.
     *
     * @return string The product ID.
     */
    public function getProductId();
}