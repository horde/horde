<?php
/**
 * Handles iTip response options for Kolab iTip responses.
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
 * Handles iTip response options for Kolab iTip responses.
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
class Horde_Itip_Response_Options_Kolab
extends Horde_Itip_Response_Options_Base
{
    /**
     * Prepare the iCalendar MIME part of the response message.
     *
     * @param Horde_Mime_Part $ics The iCalendar MIME part of the response
     *                             message.
     *
     * @return NULL
     */
    public function prepareResponseMimeHeaders(Horde_Mime_Headers $headers)
    {
    }

    /**
     * Get the character set for the response mime parts.
     *
     * @return string The character set.
     */
    public function getCharacterSet()
    {
        return 'UTF-8';
    }

    /**
     * Get the product ID of the iCalendar object embedded in the MIME response.
     *
     * @return string The product ID.
     */
    public function getProductId()
    {
        return '-//kolab.org//NONSGML Kolab Server 2//EN';
    }
}