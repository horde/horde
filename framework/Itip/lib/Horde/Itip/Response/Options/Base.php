<?php
/**
 * Holds iTip response options.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */

/**
 * Holds iTip response options.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * {@link http://www.horde.org/licenses/lgpl21 LGPL}.
 *
 * @category Horde
 * @package  Itip
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://pear.horde.org/index.php?package=Itip
 */
abstract class Horde_Itip_Response_Options_Base
implements Horde_Itip_Response_Options
{
    /**
     * Prepare the iCalendar part of the response object.
     *
     * @param Horde_Icalendar $ical The iCalendar response object.
     *
     * @return NULL
     */
    public function prepareIcalendar(Horde_Icalendar $ical)
    {
        $ical->setAttribute('PRODID', $this->getProductId());
    }

    /**
     * Prepare the iCalendar MIME part of the response message.
     *
     * @param Horde_Mime_Part $ics The iCalendar MIME part of the response
     *                             message.
     *
     * @return NULL
     */
    public function prepareIcsMimePart(Horde_Mime_Part $ics)
    {
        $ics->setCharset($this->getCharacterSet());
    }

    /**
     * Prepare the message MIME part of the response.
     *
     * @param Horde_Mime_Part $message The message MIME part of the response.
     *
     * @return NULL
     */
    public function prepareMessageMimePart(Horde_Mime_Part $message)
    {
        $message->setCharset($this->getCharacterSet());
    }
}