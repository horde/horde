<?php
/**
 * Copyright 2013-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */

/**
 * Spam reporting driver via e-mail.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @author    Jan Schneider <jan@horde.org>
 * @category  Horde
 * @copyright 2013-2016 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL
 * @package   Spam
 */
class Horde_Spam_Email extends Horde_Spam_Base
{
    /**
     * Mail transport.
     *
     * @var Horde_Mail_Transport
     */
    protected $_mail;

    /**
     * Target e-mail address.
     *
     * @var string
     */
    protected $_email;

    /**
     * Sending e-mail address.
     *
     * @var string
     */
    protected $_from_addr;

    /**
     * The reporting user.
     *
     * @var string
     */
    protected $_user;

    /**
     * E-mail format.
     *
     * @var string
     */
    protected $_format;

    /**
     * Additional options.
     *
     * @var array
     */
    protected $_opts;

    /**
     * Constructor.
     *
     * @param Horde_Mail_Transport $mail  The mail transport to use.
     * @param string $email               E-mail address to send reports to.
     * @param string $from                E-mail address to send reports from.
     * @param string $user                A user name for information purposes.
     * @param string $format              E-mail format. Either 'redirect' or
     *                                    'digest'.
     * @param array $opts                 Additional options:
     *   - digest_limit_msgs: (integer) Maximum number of messages allowed in
     *                        a digest.
     *   - digest_limit_size: (integer) Maximum size of a digest.
     */
    public function __construct(
        $mail, $email, $from, $user, $format, array $opts = array())
    {
        parent::__construct();
        $this->_mail = $mail;
        $this->_email = $email;
        $this->_from_addr = $from;
        $this->_user = $user;
        $this->_format = $format;
        $this->_opts = $opts;
    }

    /**
     */
    public function report(array $msgs, $action)
    {
        $ret = 0;

        switch ($this->_format) {
        case 'redirect':
            /* Send the message. */
            try {
                foreach ($msgs as $val) {
                    $val = $this->_makeStream($val);
                    $this->_redirectMessage($val);
                    $ret++;
                }
            } catch (Horde_Spam_Exception $e) {
                $this->_logger->err($e);
                return 0;
            }
            break;

        case 'digest':
        default:
            $mlimit = $orig_mlimit = empty($this->_opts['digest_limit_msgs'])
                ? null
                : $this->_opts['digest_limit_msgs'];
            $slimit = $orig_slimit = empty($this->_opts['digest_limit_size'])
                ? null
                : $this->_opts['digest_limit_size'];

            $todo = array();

            foreach ($msgs as $val) {
                $val = $this->_makeStream($val);
                $process = false;
                $todo[] = $val;

                if (!is_null($mlimit) && !(--$mlimit)) {
                    $process = true;
                }

                if (!is_null($slimit) &&
                    (($slimit -= $val->length()) < 0)) {
                    $process = true;
                    /* If we have exceeded size limits with this single
                     * message, it exceeds the maximum limit and we can't
                     * send it at all. Don't confuse the user and instead
                     * report is as a "success" for UI purposes. */
                    if (count($todo) === 1) {
                        ++$ret;
                        $todo = array();
                        $this->_logger->notice(
                            'Could not send spam/innocent reporting message because original message was too large.'
                        );
                    }
                }

                if ($process) {
                    $ret += $this->_reportDigest($todo, $action);
                    $todo = array();
                    $mlimit = $orig_mlimit;
                    $slimit = $orig_slimit;
                }
            }

            $ret += $this->_reportDigest($todo, $action);
            break;
        }

        return $ret;
    }

    /**
     * Sends a redirect (a/k/a resent) message.
     *
     * @param Horde_Stream $message  Message content.
     *
     * @throws Horde_Spam_Exception
     */
    protected function _redirectMessage(Horde_Stream $message)
    {
        /* Split up headers and message. */
        $message->rewind();
        $eol = $message->getEOL();
        $headers = $message->getToChar($eol . $eol);
        if (!strlen($headers)) {
            throw new Horde_Spam_Exception('Invalid message reported, header not found');
        }
        $body = fopen('php://temp', 'r+');
        stream_copy_to_stream($message->stream, $body, -1, $message->pos());

        /* We need to set the Return-Path header to the sending user - see RFC
         * 2821 [4.4]. */
        $headers = preg_replace(
            '/return-path:.*?(\r?\n)/i',
            'Return-Path: <' . $this->_from_addr . '>$1',
            $headers
        );

        try {
            $this->_mail->send(
                $this->_email,
                array('_raw' => $headers, 'from' => $this->_from_addr),
                $body
            );
        } catch (Horde_Mail_Exception $e) {
            $this->_logger->warn($e);
            throw new Horde_Spam_Exception($e);
        }
    }

    /**
     * Builds and sends a digest message.
     *
     * @param array $messages  List of message contents (string|resource).
     * @param integer $action  Either Horde_Spam::SPAM or Horde_Spam::INNOCENT.
     *
     * @return integer  The number of reported messages.
     */
    protected function _reportDigest(array $messages, $action)
    {
        if (empty($messages)) {
            return 0;
        }

        /* Build the MIME structure. */
        $mime = new Horde_Mime_Part();
        $mime->setType('multipart/digest');

        foreach ($messages as $val) {
            $rfc822 = new Horde_Mime_Part();
            $rfc822->setType('message/rfc822');
            $rfc822->setContents($val);
            $mime[] = $rfc822;
        }

        $spam_headers = new Horde_Mime_Headers();
        $spam_headers->addHeaderOb(
            Horde_Mime_Headers_MessageId::create()
        );
        $spam_headers->addHeaderOb(
            Horde_Mime_Headers_Date::create()
        );
        $spam_headers->addHeader('To', $this->_email);
        $spam_headers->addHeader('From', $this->_from_addr);
        $spam_headers->addHeader(
            'Subject',
            sprintf(
                Horde_Spam_Translation::t("%s report from %s"),
                ($action === Horde_Spam::SPAM) ? 'spam' : 'innocent',
                $this->_user
            )
        );

        /* Send the message. */
        try {
            $mime->send(
                $this->_email,
                $spam_headers,
                $this->_mail
            );
            return count($messages);
        } catch (Horde_Mail_Exception $e) {
            $this->_logger->warn($e);
            return 0;
        }
    }

    /**
     * Converts a string or resource into a Horde_Stream object.
     *
     * @param string|resource $val  Some content.
     *
     * @return Horde_Stream  A Horde_Stream object with the content.
     */
    protected function _makeStream($val)
    {
        if (is_resource($val)) {
            return new Horde_Stream_Existing(array('stream' => $val));
        }
        $tmp = new Horde_Stream();
        $tmp->add($val);
        return $tmp;
    }
}
