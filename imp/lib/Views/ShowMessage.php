<?php
/**
 * Dynamic (dimp) message display logic.
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
class IMP_Views_ShowMessage
{
    /**
     * Builds a list of addresses from header information.
     *
     * @param array $addrlist  The list of addresses from
     *                         Horde_Mime_Address::parseAddressList().
     *
     * @return array  Array with the following keys: inner, personal, raw.
     */
    private function _buildAddressList($addrlist)
    {
        if (empty($addrlist) || !is_array($addrlist)) {
            return;
        }

        $addr_array = array();

        foreach (Horde_Mime_Address::getAddressesFromObject($addrlist, array('charset' => 'UTF-8')) as $ob) {
            if (!empty($ob['inner'])) {
                try {
                    $tmp = array('raw' => Horde::callHook('dimp_addressformatting', array($ob), 'imp'));
                } catch (Horde_Exception_HookNotSet $e) {
                    $tmp = array('inner' => $ob['inner']);
                    if (!empty($ob['personal'])) {
                        $tmp['personal'] = $ob['personal'];
                    }
                }
                $addr_array[] = $tmp;
            }
        }

        return $addr_array;
    }

    /**
     * Create the object used to display the message.
     *
     * @param array $args  Configuration parameters:
     *   - headers: (array) The headers desired in the returned headers array
     *              (only used with non-preview view).
     *   - mailbox: (IMP_Mailbox) The mailbox of the message.
     *   - preview: (boolean) Is this the preview view?
     *   - uid: (integer) The UID of the message.
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
     *   - log: Log information
     *   - mbox: The mailbox (base64url encoded)
     *   - msgtext: The text of the message
     *   - priority (FULL): The priority of the message (low, high, normal)
     *   - replyTo (FULL): The Reply-to addresses
     *   - save_as: The save link
     *   - subject: The subject
     *   - title (FULL): The title of the page
     *   - to: The To addresses
     *   - uid: The message UID
     *
     * @throws IMP_Exception
     */
    public function showMessage($args)
    {
        $preview = !empty($args['preview']);
        $mailbox = $args['mailbox'];
        $uid = $args['uid'];

        $result = array(
            'js' => array(),
            'mbox' => $mailbox->form_to,
            'uid' => $uid
        );

        /* Set the current time zone. */
        $GLOBALS['registry']->setTimeZone();

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();

            $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
            $fetch_ret = $imp_imap->fetch($mailbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            if (!isset($fetch_ret[$uid])) {
                throw new Exception();
            }

            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($mailbox->getIndicesOb($uid));
        } catch (Exception $e) {
            throw new IMP_Exception(_("Requested message not found."));
        }

        $envelope = $fetch_ret[$uid]->getEnvelope();
        $mime_headers = $imp_contents->getHeaderAndMarkAsSeen();
        $headers = array();

        /* Initialize variables. */
        if (!$preview) {
            $imp_hdr_ui = new IMP_Ui_Headers();
        }
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
                $tmp = $this->_buildAddressList(($val == 'reply-to') ? $envelope->reply_to : $envelope->$val);
                if (!empty($tmp)) {
                    $result[$val] = $tmp;
                } elseif ($val == 'to') {
                    $result[$val] = array(array('raw' => _("Undisclosed Recipients")));
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
                    $val = htmlspecialchars($imp_ui->getLocalTime($envelope->date));
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
            (Horde_Mime_Address::bareAddress($result['from'][0]['inner']) == Horde_Mime_Address::bareAddress($result['reply-to'][0]['inner']))) {
            unset($result['reply-to'], $headers['reply-to']);
        }

        /* JS requires camelized name for reply-to. */
        if (!$preview && isset($headers['reply-to'])) {
            $result['replyTo'] = $result['reply-to'];
            $headers['reply-to']['id'] = 'ReplyTo';
            unset($result['reply-to']);
        }

        /* Grab maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog']) &&
            ($tmp = IMP_Dimp::getMsgLogInfo($envelope->message_id))) {
            $result['log'] = $tmp;
        }

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
            $result['subject'] = $imp_ui->getDisplaySubject($subject);
            if (!$preview) {
                $result['title'] = htmlspecialchars($subject);
            }
        } else {
            $result['subject'] = htmlspecialchars(_("[No Subject]"));
            if (!$preview) {
                $result['title'] = htmlspecialchars(_("[No Subject]"));
            }
        }

        /* Get message priority. */
        if (!$preview) {
            $result['priority'] = $imp_hdr_ui->getPriority($mime_headers);
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

        /* Allow stripping of attachments? */
        if ($GLOBALS['prefs']->getValue('strip_attachments')) {
            $contents_mask |= IMP_Contents::SUMMARY_STRIP_STUB;
            $part_info[] = 'strip';
            $part_info_display[] = 'strip';
        }

        /* Do MDN processing now. */
        if ($imp_ui->MDNCheck($mailbox, $uid, $mime_headers)) {
            $status = new IMP_Mime_Status(array(
                _("The sender of this message is requesting notification from you when you have read this message."),
                sprintf(_("Click %s to send the notification message."), Horde::link('#', '', '', '', '', '', '', array('id' => 'send_mdn_link')) . _("HERE") . '</a>')
            ));
            $status->domid('sendMdnMessage');
            $result['msgtext'] .= strval($status);
        }

        /* Build body text. This needs to be done before we build the
         * attachment list. */
        $inlineout = $imp_contents->getInlineOutput(array(
            'mask' => $contents_mask,
            'part_info_display' => $part_info_display,
            'show_parts' => $show_parts
        ));

        $result['js'] = array_merge($result['js'], $inlineout['js_onload']);
        $result['msgtext'] .= $inlineout['msgtext'];

        if (count($inlineout['atc_parts']) ||
            (($show_parts == 'all') && count($inlineout['display_ids']) > 2)) {
            $result['atc_label'] = ($show_parts == 'all')
                ? _("Parts")
                : sprintf(ngettext("%d Attachment", "%d Attachments", count($inlineout['atc_parts'])), count($inlineout['atc_parts']));
            if (count($inlineout['atc_parts']) > 2) {
                $result['atc_download'] = Horde::link($imp_contents->urlView($imp_contents->getMIMEMessage(), 'download_all')) . '[' . _("Save All") . ']</a>';
            }
        }

        /* Show attachment information in headers? */
        if (!empty($inlineout['atc_parts'])) {
            $tmp = '';

            if ($show_parts == 'all') {
                array_unshift($part_info, 'id');
            }

            foreach ($inlineout['atc_parts'] as $id) {
                $summary = $imp_contents->getSummary($id, $contents_mask);
                $tmp .= '<tr>';
                foreach ($part_info as $val) {
                    $tmp .= '<td' .
                        (strlen($summary[$val]) ? '' : ' class="partlistempty"') .
                        '>' . $summary[$val] . '</td>';
                }
                $tmp .= '</tr>';
            }

            $result['atc_list'] = $tmp;
        }

        $result['save_as'] = Horde::downloadUrl(htmlspecialchars_decode($result['subject']), array_merge(array('actionID' => 'save_message'), $mailbox->urlParams($uid)));

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
            Horde::outputInlineScript(true);
            if ($js_inline = Horde::endBuffer()) {
                $result['js'][] = $js_inline;
            }

            $result['save_as'] = strval($result['save_as']->setRaw(true));
        } else {
            try {
                $result = Horde::callHook('dimp_messageview', array($result), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}

            $result['list_info'] = $imp_ui->getListInformation($mime_headers);
        }

        if (empty($result['js'])) {
            unset($result['js']);
        }

        /* Add changed flag information. */
        if ($imp_imap->imap) {
            $status = $imp_imap->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
            if (in_array(Horde_Imap_Client::FLAG_SEEN, $status['permflags'])) {
                $GLOBALS['injector']->getInstance('IMP_Ajax_Queue')->flag(array(Horde_Imap_Client::FLAG_SEEN), true, $mailbox->getIndicesOb($uid));
            }
        }

        return $result;
    }

}
