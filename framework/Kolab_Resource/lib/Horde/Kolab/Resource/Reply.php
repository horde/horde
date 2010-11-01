<?php
/**
 * Represents a reply for an iTip inviation.
 *
 * PHP version 5
 * 
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Represents a reply for an iTip inviation.
 *
 * Copyright 2004-2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL>=2.1). If you
 * did not receive this file,
 * see http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Resource_Reply
{
    /**
     * Sender of the iTip reply.
     *
     * @var string
     */
    protected $_sender;

    /**
     * Recipient of the iTip reply.
     *
     * @var string
     */
    protected $_recipient;

    /**
     * Reply headers.
     *
     * @var MIME_Headers
     */
    protected $_headers;

    /**
     * Reply body.
     *
     * @var MIME_Message
     */
    protected $_body;

    /**
     * Constructor.
     *
     * @param string       $sender    Sender of the iTip reply.
     * @param string       $recipient Recipient of the iTip reply.
     * @param MIME_Headers $headers   Reply headers.
     * @param MIME_Message $body      Reply body.
     */
    public function __construct(
        $sender, $recipient, MIME_Headers $headers, MIME_Message $body
    ) {
        $this->_sender    = $sender;
        $this->_recipient = MIME::encodeAddress($recipient);
        $this->_headers   = $headers;
        $this->_body      = $body;
    }

    public function getSender()
    {
        return $this->_sender;
    }

    public function getRecipient()
    {
        return $this->_recipient;
    }

    public function getData()
    {
        return $this->_headers->toString() . '\r\n\r\n' . $this->_body->toString();
    }
}