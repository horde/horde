<?php
/**
 * Dynamic (dimp) message display logic.
 *
 * Copyright 2005-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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

        foreach (Horde_Mime_Address::getAddressesFromObject($addrlist, array('charset' => $GLOBALS['registry']->getCharset())) as $ob) {
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
     * @param array $args  Configuration parameters.
     * <pre>
     * 'headers' - (array) The headers desired in the returned headers array
     *             (only used with non-preview view)
     * 'mailbox' - (string) The mailbox name
     * 'preview' - (boolean) Is this the preview view?
     * 'uid' - (integer) The UID of the message
     * </pre>
     *
     * @return array  Array with the following keys:
     * <pre>
     * FOR BOTH MODES:
     * 'atc_download' - The download all link
     * 'atc_label' - The label to use for Attachments
     * 'atc_list' - The list (HTML code) of attachments
     * 'cc' - The CC addresses
     * 'error' - Contains an error message (only on error)
     * 'errortype' - Contains the error type (only on error)
     * 'from' - The From addresses
     * 'js' - Javascript code to run on display
     * 'log' - Log information
     * 'mailbox' - The IMAP mailbox
     * 'msgtext' - The text of the message
     * 'subject' - The subject
     * 'to' - The To addresses
     * 'uid' - The IMAP UID
     *
     * FOR PREVIEW MODE:
     * 'localdate' - The date formatted to the user's timezone
     *
     * FOR NON-PREVIEW MODE:
     * 'bcc' - The Bcc addresses
     * 'headers' - An array of headers (not including basic headers)
     * 'list_info' - List information.
     * 'priority' - The priority of the message ('low', 'high', 'normal')
     * 'replyTo' - The Reply-to addresses
     * 'save_as' - The save link
     * 'title' - The title of the page
     * </pre>
     */
    public function showMessage($args)
    {
        $preview = !empty($args['preview']);
        $mailbox = $args['mailbox'];
        $uid = $args['uid'];
        $error_msg = _("Requested message not found.");

        $result = array(
            'js' => array(),
            'mailbox' => $mailbox,
            'uid' => intval($uid)
        );

        /* Set the current time zone. */
        $GLOBALS['registry']->setTimeZone();

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            $fetch_ret = $GLOBALS['injector']->getInstance('IMP_Imap')->getOb()->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => false))
            ), array('ids' => array($uid)));
        } catch (Horde_Imap_Client_Exception $e) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        if (!isset($fetch_ret[$uid]['headertext'])) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        /* Parse MIME info and create the body of the message. */
        try {
            $imp_contents = $GLOBALS['injector']->getInstance('IMP_Contents')->getOb(new IMP_Indices($mailbox, $uid));
        } catch (IMP_Exception $e) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        $envelope = $fetch_ret[$uid]['envelope'];
        $mime_headers = reset($fetch_ret[$uid]['headertext']);
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
            $args['headers'] = array('from', 'date', 'to', 'cc');
        }

        $headers_list = array_intersect_key($basic_headers, array_flip($args['headers']));

        /* Build From/To/Cc/Bcc/Reply-To links. */
        foreach (array('from', 'to', 'cc', 'bcc', 'reply-to') as $val) {
            if (isset($headers_list[$val]) &&
                (!$preview || !in_array($val, array('bcc', 'reply-to')))) {
                $tmp = $this->_buildAddressList($envelope[$val]);
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
            if (!$preview && isset($result[$head])) {
                $headers[$head] = array('id' => Horde_String::ucfirst($head), 'name' => $str, 'value' => '');
            } elseif ($val = $mime_headers->getValue($head)) {
                if ($head == 'date') {
                    /* Add local time to date header. */
                    $val = htmlspecialchars($imp_ui->getLocalTime($envelope['date']));
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
            ($result['from'] == $result['reply-to'])) {
            unset($result['reply-to'], $headers['reply-to']);
        }

        /* JS requires camelized name for reply-to. */
        if (!$preview && isset($headers['reply-to'])) {
            $head = 'replyTo';
            $result['replyTo'] = $result['reply-to'];
            unset($result['reply-to']);
            $headers['reply-to']['id'] = Horde_String::ucfirst($head);
        }

        /* Grab maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog']) &&
            ($tmp = IMP_Dimp::getMsgLogInfo($envelope['message-id']))) {
            $result['log'] = $tmp;
        }

        if ($preview) {
            /* Get minidate. */
            $imp_mailbox_ui = new IMP_Ui_Mailbox();
            $localdate = $imp_mailbox_ui->getDate($envelope['date']);
            if (empty($localdate)) {
                $localdate = _("Unknown Date");
            }
            $result['localdate'] = htmlspecialchars($localdate);
        } else {
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
            IMP_Contents::SUMMARY_PRINT_STUB |
            IMP_Contents::SUMMARY_STRIP_STUB;

        $part_info = $part_info_display = array('icon', 'description', 'size', 'download', 'download_zip');
        $part_info_display[] = 'print';
        $part_info_display[] = 'strip';

        /* Do MDN processing now. */
        if ($imp_ui->MDNCheck($mailbox, $uid, $mime_headers)) {
            $result['msgtext'] .= $imp_ui->formatStatusMsg(array(array('text' => array(_("The sender of this message is requesting a Message Disposition Notification from you when you have read this message."), sprintf(_("Click %s to send the notification message."), Horde::link('', '', '', '', 'DimpCore.doAction(\'sendMDN\',{folder:\'' . $mailbox . '\',uid:' . $uid . '}); return false;', '', '') . _("HERE") . '</a>')))));
        }

        /* Build body text. This needs to be done before we build the
         * attachment list. */
        $inlineout = $imp_ui->getInlineOutput($imp_contents, array(
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
            if (count($inlineout['display_ids']) > 2) {
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
                    $tmp .= '<td>' . $summary[$val] . '</td>';
                }
                $tmp .= '</tr>';
            }

            $result['atc_list'] = $tmp;
        }

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
        } else {
            try {
                $result = Horde::callHook('dimp_messageview', array($result), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}

            $result['list_info'] = $imp_ui->getListInformation($mime_headers);
            $result['save_as'] = Horde::downloadUrl(htmlspecialchars_decode($result['subject']), array_merge(array('actionID' => 'save_message'), IMP::getIMPMboxParameters($mailbox, $uid, $mailbox)));
        }

        if (empty($result['js'])) {
            unset($result['js']);
        }

        return $result;
    }
}
