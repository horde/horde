<?php
/**
 * Message viewing action for AJAX application handler.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application_ShowMessage
{
    /**
     * Contents object.
     *
     * @var IMP_Contents
     */
    protected $_contents;

    /**
     * Envelope object.
     *
     * @var Horde_Imap_Client_Data_Envelope
     */
    protected $_envelope;

    /**
     * Don't seen seen flag?
     *
     * @var boolean
     */
    protected $_peek;

    /**
     * Mailbox.
     *
     * @var IMP_Mailbox
     */
    protected $_mbox;

    /**
     * UID.
     *
     * @var string
     */
    protected $_uid;

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mbox  The mailbox of the message.
     * @param integer $uid       The UID of the message.
     * @param boolean $peek      Don't set seen flag?
     */
    public function __construct(IMP_Mailbox $mbox, $uid, $peek = false)
    {
        global $injector;

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();

            $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
            $ret = $imp_imap->fetch($mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            if (!($ob = $ret->first())) {
                throw new Exception();
            }

            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create($mbox->getIndicesOb($uid));
        } catch (Exception $e) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $this->_contents = $imp_contents;
        $this->_envelope = $ob->getEnvelope();
        $this->_mbox = $mbox;
        $this->_peek = $peek;
        $this->_uid = $uid;
    }

    /**
     * Create the object used to display the message.
     *
     * @param array $args  Configuration parameters:
     *   - headers: (array) The headers desired in the returned headers array
     *              (only used with non-preview view).
     *   - preview: (boolean) Is this the preview view?
     *
     * @return array  Array with the following keys:
     *   - atc_download: The download all link
     *   - atc_label: The label to use for Attachments
     *   - atc_list: The list (HTML code) of attachments
     *   - bcc (FULL): The Bcc addresses
     *   - cc: The CC addresses
     *   - from: The From addresses
     *   - headers (FULL): An array of headers (not including basic headers)
     *   - js: Javascript code to run on display
     *   - list_info (FULL): List information.
     *   - localdate (PREVIEW): The date formatted to the user's timezone
     *   - mbox: The mailbox (base64url encoded)
     *   - msgtext: The text of the message
     *   - onepart: True if message only contains one part.
     *   - replyTo (FULL): The Reply-to addresses
     *   - save_as: The save link
     *   - subject: The subject
     *   - subjectlink: The subject with linked URLs/email addresses (defaults
     *                  to 'subject')
     *   - title (FULL): The title of the page
     *   - to: The To addresses
     *   - uid: The message UID
     *
     * @throws IMP_Exception
     */
    public function showMessage($args)
    {
        $preview = !empty($args['preview']);

        $result = array(
            'js' => array(),
            'mbox' => $this->_mbox->form_to,
            'uid' => $this->_uid
        );

        /* Set the current time zone. */
        $GLOBALS['registry']->setTimeZone();

        $mime_headers = $this->_peek
            ? $this->_contents->getHeader()
            : $this->_contents->getHeaderAndMarkAsSeen();

        $headers = array();
        $imp_ui = new IMP_Ui_Message();

        /* Develop the list of Headers to display now. Deal with the 'basic'
         * header information first since there are various manipulations
         * done to them. */
        $basic_headers = $imp_ui->basicHeaders();
        if (empty($args['headers'])) {
            $args['headers'] = array('from', 'date', 'to', 'cc', 'bcc');
        }

        $headers_list = array_intersect_key($basic_headers, array_flip($args['headers']));

        /* Build From/To/Cc/Bcc/Reply-To links. */
        foreach (array('from', 'to', 'cc', 'bcc', 'reply-to') as $val) {
            if (isset($headers_list[$val]) &&
                (!$preview || ($val != 'reply-to'))) {
                if ($tmp = $this->getAddressHeader($val)) {
                    $result[$val] = $tmp;
                }
                if ($preview) {
                    unset($headers_list[$val]);
                }
            }
        }

        /* Build the rest of the headers. */
        foreach ($headers_list as $head => $str) {
            if ($val = $mime_headers->getValue($head)) {
                if ($head == 'date') {
                    /* Add local time to date header. */
                    $val = htmlspecialchars($imp_ui->getLocalTime($this->_envelope->date));
                    if ($preview) {
                        $result['localdate'] = $val;
                    }
                } elseif (!$preview) {
                    $val = htmlspecialchars($val);
                }

                if (!$preview) {
                    $headers[$head] = array('id' => Horde_String::ucfirst($head), 'name' => $str, 'value' => $val);
                }
            }
        }

        if (empty($result['reply-to']) ||
            ($result['from']['addr'][0]['b'] == $result['reply-to']['addr'][0]['b'])) {
            unset($result['reply-to'], $headers['reply-to']);
        }

        /* JS requires camelized name for reply-to. */
        if (!$preview && isset($headers['reply-to'])) {
            $result['replyTo'] = $result['reply-to'];
            $headers['reply-to']['id'] = 'ReplyTo';
            unset($result['reply-to']);
        }

        /* Maillog information. */
        $GLOBALS['injector']->getInstance('IMP_Ajax_Queue')->maillog($this->_mbox, $this->_uid, $this->_envelope->message_id);

        if (!$preview) {
            /* Display the user-specified headers for the current identity. */
            $user_hdrs = $imp_ui->getUserHeaders();
            foreach ($user_hdrs as $user_hdr) {
                $user_val = $mime_headers->getValue($user_hdr);
                if (!empty($user_val)) {
                    $headers[] = array('name' => $user_hdr, 'value' => htmlspecialchars($user_val));
                }
            }
            $result['headers'] = array_values($headers);
        }

        /* Process the subject. */
        $subject = $mime_headers->getValue('subject');
        if ($subject) {
            $result['subject'] = $imp_ui->getDisplaySubject($subject, Horde_Text_Filter_Text2html::NOHTML);
            $subjectlink = $imp_ui->getDisplaySubject($subject);
            if ($subjectlink != $result['subject']) {
                $result['subjectlink'] = $subjectlink;
            }
            if (!$preview) {
                $result['title'] = $subject;
            }
        } else {
            $result['subject'] = _("[No Subject]");
            if (!$preview) {
                $result['title'] = _("[No Subject]");
            }
        }

        // Create message text and attachment list.
        $result['msgtext'] = '';
        $show_parts = $GLOBALS['prefs']->getValue('parts_display');

        $contents_mask = IMP_Contents::SUMMARY_BYTES |
            IMP_Contents::SUMMARY_SIZE |
            IMP_Contents::SUMMARY_ICON |
            IMP_Contents::SUMMARY_DESCRIP_LINK |
            IMP_Contents::SUMMARY_DOWNLOAD |
            IMP_Contents::SUMMARY_DOWNLOAD_ZIP |
            IMP_Contents::SUMMARY_PRINT_STUB;

        $part_info = $part_info_display = array('icon', 'description', 'size', 'download', 'download_zip');
        $part_info_display[] = 'print';

        /* Do MDN processing now. */
        if ($imp_ui->MDNCheck($this->_mbox, $this->_uid, $mime_headers)) {
            $status = new IMP_Mime_Status(array(
                _("The sender of this message is requesting notification from you when you have read this message."),
                sprintf(_("Click %s to send the notification message."), Horde::link('#', '', '', '', '', '', '', array('id' => 'send_mdn_link')) . _("HERE") . '</a>')
            ));
            $status->domid('sendMdnMessage');
            $result['msgtext'] .= strval($status);
        }

        /* Build body text. This needs to be done before we build the
         * attachment list. */
        $this->_contents->fetchCloseSession = true;
        $inlineout = $this->_contents->getInlineOutput(array(
            'mask' => $contents_mask,
            'part_info_display' => $part_info_display,
            'show_parts' => $show_parts
        ));
        $this->_contents->fetchCloseSession = false;

        $result['js'] = array_merge($result['js'], $inlineout['js_onload']);
        $result['msgtext'] .= $inlineout['msgtext'];
        if ($inlineout['one_part']) {
            $result['onepart'] = true;
        }

        if (count($inlineout['atc_parts']) ||
            (($show_parts == 'all') && count($inlineout['display_ids']) > 2)) {
            $result['atc_label'] = ($show_parts == 'all')
                ? _("Parts")
                : sprintf(ngettext("%d Attachment", "%d Attachments", count($inlineout['atc_parts'])), count($inlineout['atc_parts']));
            if (count($inlineout['atc_parts']) > 2) {
                $result['atc_download'] = Horde::link($this->_contents->urlView($this->_contents->getMIMEMessage(), 'download_all')) . '[' . _("Save All") . ']</a>';
            }
        }

        /* Show attachment information in headers? */
        if (!empty($inlineout['atc_parts'])) {
            $partlist = array();

            if ($show_parts == 'all') {
                array_unshift($part_info, 'id');
            }

            foreach ($inlineout['atc_parts'] as $id) {
                $contents_mask |= IMP_Contents::SUMMARY_DESCRIP;
                $part_info[] = 'description_raw';
                $part_info[] = 'download_url';

                $summary = $this->_contents->getSummary($id, $contents_mask);
                $tmp = array();
                foreach ($part_info as $val) {
                    if (strlen($summary[$val])) {
                        $tmp[$val] = ($summary[$val] instanceof Horde_Url)
                            ? strval($summary[$val]->setRaw(true))
                            : $summary[$val];
                    }
                }
                $partlist[] = $tmp;
            }

            $result['atc_list'] = $partlist;
        }

        $result['save_as'] = $GLOBALS['registry']->downloadUrl(htmlspecialchars_decode($result['subject']), array_merge(array('actionID' => 'save_message'), $this->_mbox->urlParams($this->_uid)));

        if ($preview) {
            try {
                $res = Horde::callHook('dimp_previewview', array($result), 'imp');
                if (!empty($res)) {
                    $result = $res[0];
                    $result['js'] = array_merge($result['js'], $res[1]);
                }
            } catch (Horde_Exception_HookNotSet $e) {}

            /* Need to grab cached inline scripts. */
            Horde::startBuffer();
            $GLOBALS['page_output']->outputInlineScript(true);
            if ($js_inline = Horde::endBuffer()) {
                $result['js'][] = $js_inline;
            }

            $result['save_as'] = strval($result['save_as']->setRaw(true));
        } else {
            try {
                $result = Horde::callHook('dimp_messageview', array($result), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}

            $list_info = $imp_ui->getListInformation($mime_headers);
            if (!empty($list_info['exists'])) {
                $result['list_info'] = $list_info;
            }
        }

        if (empty($result['js'])) {
            unset($result['js']);
        }

        /* Add changed flag information. */
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        if (!$this->_peek && $imp_imap->imap) {
            $status = $imp_imap->status($this->_mbox, Horde_Imap_Client::STATUS_PERMFLAGS);
            if (in_array(Horde_Imap_Client::FLAG_SEEN, $status['permflags'])) {
                $GLOBALS['injector']->getInstance('IMP_Ajax_Queue')->flag(array(Horde_Imap_Client::FLAG_SEEN), true, $this->_mbox->getIndicesOb($this->_uid));
            }
        }

        return $result;
    }

    /**
     * Return data to build an address header.
     *
     * @param string $header  The address header.
     * @param integer $limit  Limit display to this many addresses. If null,
     *                        shows all addresses.
     *
     * @return array  An array with the following entries:
     *   - addr: (array) List of addresses/groups.
     *           Group keys: 'a' (list of addresses); 'g' (group name)
     *           Address keys: 'b' (bare address); 'p' (personal part)
     *   - limit: (integer) If limit was reached, the number of total
     *            addresses.
     *   - raw: (string) A raw string to display instead of addresses.
     */
    public function getAddressHeader($header, $limit = 50)
    {
        $addrlist = ($header == 'reply-to')
            ? $this->_envelope->reply_to
            : $this->_envelope->$header;
        $addr_array = $out = array();
        $count = 0;

        $addrlist->unique();

        foreach ($addrlist->base_addresses as $ob) {
            if ($ob instanceof Horde_Mail_Rfc822_Group) {
                $tmp = array(
                    'g' => $ob->groupname
                );

                $count += count($ob->addresses);
                if (is_null($limit) || ($count <= $limit)) {
                    $tmp['a'] = array();
                    foreach ($ob->addresses as $val) {
                        $tmp['a'][] = array(
                            'b' => $val->bare_address,
                            'p' => $val->personal
                        );
                    }
                }
            } else {
                $tmp = array(
                    'b' => $ob->bare_address,
                    'p' => $ob->personal
                );
                ++$count;
            }

            $addr_array[] = $tmp;

            if (!is_null($limit) && ($count > $limit)) {
                $out['limit'] = count($addrlist);
                break;
            }
        }

        if (!empty($addr_array)) {
            $out['addr'] = $addr_array;
        } elseif ($header == 'to') {
            $out['raw'] = _("Undisclosed Recipients");
        }

        return $out;
    }

}
