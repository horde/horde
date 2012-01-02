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
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
extends Horde_Push_Recipient_Base
{
    /**
     * The mail transport.
     *
     * @var Horde_Mail_Transport
     */
    private $_mail;

    /**
     * Mail parameters.
     *
     * @var array
     */
    private $_params;

    /**
     * Constructor.
     *
     * @param Horde_Mail_Transport $mail   The mail transport.
     * @param array                $params Parameters for the mail transport.
     */
    public function __construct(Horde_Mail_Transport $mail, $params = array())
    {
        $this->_mail = $mail;
        $this->_params = $params;
    }

    /**
     * Push content to the recipient.
     *
     * @param Horde_Push $content The content element.
     * @param array      $options Additional options.
     *
     * @return NULL
     */
    public function push(Horde_Push $content, $options = array())
    {
        $contents = $content->getContent();
        $types = $content->getMimeTypes();
        $mail = new Horde_Mime_Mail();
        // @todo Append references
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
        $mail->addRecipients(explode(',', $this->getAcl()));
        $mail->addHeader('subject', $content->getSummary());
        if (!empty($this->_params['from'])) {
            $mail->addHeader('from', $this->_params['from']);
        }
        $mail->addHeader('to', $this->getAcl());

        if (!empty($options['pretend'])) {
            $mock = new Horde_Mail_Transport_Mock();
            $mail->send($mock);
            return sprintf(
                "Would push mail \n\n%s\n\n%s\n to %s.",
                $mock->sentMessages[0]['header_text'],
                $mock->sentMessages[0]['body'],
                $this->getAcl()
            );
        }

        $mail->send($this->_mail);
        return sprintf(
            'Pushed mail to %s.', $this->getAcl()
        );
    }
}
