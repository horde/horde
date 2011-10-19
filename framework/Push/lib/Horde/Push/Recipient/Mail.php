<?php
/**
 * E-mail recipients.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */

/**
 * E-mail recipients.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Push
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Push
 */
class Horde_Push_Recipient_Mail
implements Horde_Push_Recipient
{
    /**
     * The mail transport.
     *
     * @var Horde_Mail_Transport
     */
    private $_mail;

    /**
     * The mail recipients.
     *
     * @var array
     */
    private $_recipients;

    /**
     * Constructor.
     *
     * @param Horde_Mail_Transport $mail       The mail transport.
     * @param array                $recipients The mail recipients.
     */
    public function __construct(Horde_Mail_Transport $mail, $recipients)
    {
        $this->_mail = $mail;
        $this->_recipients = $recipients;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     *
     * @return NULL
     */
    public function push(Horde_Push $content)
    {
        $contents = $content->getContent();
        $types = $content->getMimeTypes();
        $mail = new Horde_Mime_Mail();
        if (isset($types['text/plain'])) {
            $mail->setBody($content->getStringContent($types['text/plain'][0]));
            unset($contents[$types['text/plain'][0]]);
        }
        if (isset($types['text/html'])) {
            $mail->setHtmlBody(
                $content->getStringContent($types['text/html'][0]),
                'UTF-8',
                !isset($types['text/plain'])
            );
            unset($contents[$types['text/html'][0]]);
        }
        foreach ($contents as $part) {
            $mail->addPart(
                $part['mime_type'],
                $part['content'],
                'UTF-8'
            );
        }
        $mail->addRecipients($this->_recipients);
        $mail->addHeader('summary', $content->getSummary());
        $mail->send($this->_mail);
    }
}
