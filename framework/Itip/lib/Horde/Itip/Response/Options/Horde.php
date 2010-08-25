<?php
/**
 * Handles iTip response options for Horde iTip responses.
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
 * Handles iTip response options for Horde iTip responses.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
class Horde_Itip_Response_Options_Horde
extends Horde_Itip_Response_Options_Base
{
    /**
     * The MIME character set.
     *
     * @var string
     */
    private $_charset;

    /**
     * Options for setting the "Received" MIME header.
     *
     * @var array
     */
    private $_received_options;

    /**
     * Constructor.
     *
     * @param string $charset          The MIME character set that should be
     *                                 used.
     * @param array  $received_options Options for setting the "Received" MIME
     *                                 header.
     */
    public function __construct($charset, $received_options)
    {
        $this->_charset          = $charset;
        $this->_received_options = $received_options;
    }
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
        $headers->addReceivedHeader($this->_received_options);
        $headers->addMessageIdHeader();
    }

    /**
     * Get the character set for the response mime parts.
     *
     * @return string The character set.
     */
    public function getCharacterSet()
    {
        return $this->_charset;
    }

    /**
     * Get the product ID of the iCalendar object embedded in the MIME response.
     *
     * @return string The product ID.
     */
    public function getProductId()
    {
        $headers = new Horde_Mime_Headers();
        return '-//The Horde Project//' . $headers->getUserAgent() . '//EN';
    }
}