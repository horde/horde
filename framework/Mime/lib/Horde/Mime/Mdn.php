<?php
/**
 * The Horde_Mime_Mdn:: class implements Message Disposition Notifications as
 * described by RFC 3798.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class Horde_Mime_Mdn
{
    /**
     * The Horde_Mime_Headers object.
     *
     * @var Horde_Mime_Headers
     */
    protected $_headers;

    /**
     * The text of the original message.
     *
     * @var string
     */
    protected $_msgtext = false;

    /**
     * Constructor.
     *
     * @param Horde_Mime_Headers $mime_headers  A headers object.
     */
    public function __construct(Horde_Mime_Headers $headers)
    {
        $this->_headers = $headers;
    }

    /**
     * Returns the address to return the MDN to.
     *
     * @return string  The address to send the MDN to. Returns null if no
     *                 MDN is requested.
     */
    public function getMdnReturnAddr()
    {
        /* RFC 3798 [2.1] requires the Disposition-Notification-To header
         * for an MDN to be created. */
        return $this->_headers->getValue('Disposition-Notification-To');
    }

    /**
     * Is user input required to send the MDN?
     * Explicit confirmation is needed in some cases to prevent mail loops
     * and the use of MDNs for mail bombing.
     *
     * @return boolean  Is explicit user input required to send the MDN?
     */
    public function userConfirmationNeeded()
    {
        $return_path = $this->_headers->getValue('Return-Path');

        /* RFC 3798 [2.1]: Explicit confirmation is needed if there is no
         * Return-Path in the header. Also, "if the message contains more
         * than one Return-Path header, the implementation may [] treat the
         * situation as a failure of the comparison." */
        if (empty($return_path) || is_array($return_path)) {
            return true;
        }

        /* RFC 3798 [2.1]: Explicit confirmation is needed if there is more
         * than one distinct address in the Disposition-Notification-To
         * header. */
        try {
            $addr_arr = Horde_Mime_Address::parseAddressList($this->getMdnReturnAddr());
        } catch (Horde_Mime_Exception $e) {
            return false;
        }

        if (count($addr_arr) > 1) {
            return true;
        }

        /* RFC 3798 [2.1] states that "MDNs SHOULD NOT be sent automatically
         * if the address in the Disposition-Notification-To header differs
         * from the address in the Return-Path header." This comparison is
         * case-sensitive for the mailbox part and case-insensitive for the
         * host part. */
        try {
            $ret_arr = Horde_Mime_Address::parseAddressList($return_path);
        } catch (Horde_Mime_Exception $e) {
            return false;
        }

        return ($addr_arr[0]['mailbox'] == $ret_arr[0]['mailbox']) &&
               (Horde_String::lower($addr_arr[0]['host']) == Horde_String::lower($ret_arr[0]['host']));
    }

    /**
     * When generating the MDN, should we return the enitre text of the
     * original message?  The default is no - we only return the headers of
     * the original message. If the text is passed in via this method, we
     * will return the entire message.
     *
     * @param string $text  The text of the original message.
     */
    public function originalMessageText($text)
    {
        $this->_msgtext = $text;
    }

    /**
     * Generate the MDN according to the specifications listed in RFC
     * 3798 [3].
     *
     * @param boolean $action   Was this MDN type a result of a manual
     *                          action on part of the user?
     * @param boolean $sending  Was this MDN sent as a result of a manual
     *                          action on part of the user?
     * @param string $type      The type of action performed by the user.
     * <pre>
     * Per RFC 3798 [3.2.6.2] the following types are valid:
     * 'displayed'
     * 'deleted'
     * </pre>
     * @param string $name      The name of the local server.
     * @param Mail $mailer      A Mail driver.
     * @param array $opts       Additional options:
     * <pre>
     * 'charset' - (string) Default charset.
     *             DEFAULT: NONE
     * 'from_addr' - (string) From address.
     *               DEFAULT: NONE
     * </pre>
     * @param array $mod        The list of modifications.
     * <pre>
     * Per RFC 3798 [3.2.6.3] the following modifications are valid:
     * 'error'
     * </pre>
     * @param array $err        If $mod is 'error', the additional
     *                          information to provide.  Key is the type of
     *                          modification, value is the text.
     *
     * @throws Horde_Mime_Exception
     */
    public function generate($action, $sending, $type, $name, $mailer,
                             array $opts = array(), array $mod = array(),
                             array $err = array())
    {
        $opts = array_merge(array(
            'charset' => null,
            'from_addr' => null
        ), $opts);

        $to = $this->getMdnReturnAddr();
        $ua = $this->_headers->getUserAgent();

        $orig_recip = $this->_headers->getValue('Original-Recipient');
        if (!empty($orig_recip) && is_array($orig_recip)) {
            $orig_recip = $orig_recip[0];
        }

        $msg_id = $this->_headers->getValue('Message-ID');

        /* Create the Disposition field now (RFC 3798 [3.2.6]). */
        $dispo = 'Disposition: ' .
                 (($action) ? 'manual-action' : 'automatic-action') .
                 '/' .
                 (($sending) ? 'MDN-sent-manually' : 'MDN-sent-automatically') .
                 '; ' .
                 $type;
        if (!empty($mod)) {
            $dispo .= '/' . implode(', ', $mod);
        }

        /* Set up the mail headers. */
        $msg_headers = new Horde_Mime_Headers();
        $msg_headers->addMessageIdHeader();
        $msg_headers->addUserAgentHeader($ua);
        $msg_headers->addHeader('Date', date('r'));
        if ($opts['from_addr']) {
            $msg_headers->addHeader('From', $opts['from_addr']);
        }
        $msg_headers->addHeader('To', $this->getMdnReturnAddr());
        $msg_headers->addHeader('Subject', _("Disposition Notification"));

        /* MDNs are a subtype of 'multipart/report'. */
        $msg = new Horde_Mime_Part();
        $msg->setType('multipart/report');
        $msg->setContentTypeParameter('report-type', 'disposition-notification');

        /* The first part is a human readable message. */
        $part_one = new Horde_Mime_Part('text/plain');
        $part_one->setCharset($opts['charset']);
        if ($type == 'displayed') {
            $contents = sprintf(_("The message sent on %s to %s with subject \"%s\" has been displayed.\n\nThis is no guarantee that the message has been read or understood."), $this->_headers->getValue('Date'), $this->_headers->getValue('To'), $this->_headers->getValue('Subject'));
            $flowed = new Horde_Text_Flowed($contents, $opts['charset']);
            $flowed->setDelSp(true);
            $part_one->setContentTypeParameter('format', 'flowed');
            $part_one->setContentTypeParameter('DelSp', 'Yes');
            $part_one->setContents($flowed->toFlowed());
        }
        // TODO: Messages for other notification types.
        $msg->addPart($part_one);

        /* The second part is a machine-parseable description. */
        $part_two = new Horde_Mime_Part('message/disposition-notification');
        $part_two_text = array('Reporting-UA: ' . $name . '; ' . $ua . "\n");
        if (!empty($orig_recip)) {
            $part_two_text[] = 'Original-Recipient: rfc822;' . $orig_recip . "\n";
        }
        if ($opts['from_addr']) {
            $part_two_text[] = 'Final-Recipient: rfc822;' . $from_addr . "\n";
        }
        if (!empty($msg_id)) {
            $part_two_text[] = 'Original-Message-ID: rfc822;' . $msg_id . "\n";
        }
        $part_two_text[] = $dispo . "\n";
        if (in_array('error', $mod) && isset($err['error'])) {
            $part_two_text[] = 'Error: ' . $err['error'] . "\n";
        }
        $part_two->setContents($part_two_text);
        $msg->addPart($part_two);

        /* The third part is the text of the original message.  RFC 3798 [3]
         * allows us to return only a portion of the entire message - this
         * is left up to the user. */
        $part_three = new Horde_Mime_Part('message/rfc822');
        $part_three_text = array($this->_headers->toString());
        if (!empty($this->_msgtext)) {
            $part_three_text[] = $part_three->getEOL() . $this->_msgtext;
        }
        $part_three->setContents($part_three_text);
        $msg->addPart($part_three);

        return $msg->send($to, $msg_headers, $mailer);
    }

    /**
     * Add a MDN (read receipt) request headers to the Horde_Mime_Headers::
     * object.
     *
     * @param string $to  The address the receipt should be mailed to.
     */
    public function addMdnRequestHeaders($to)
    {
        /* This is the RFC 3798 way of requesting a receipt. */
        $this->_headers->addHeader('Disposition-Notification-To', $to);
    }

}
