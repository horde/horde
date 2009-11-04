<?php
/**
 * Dynamic (dimp) message display logic.
 *
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
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
     * @throws Horde_Exception
     */
    private function _buildAddressList($addrlist)
    {
        if (empty($addrlist) || !is_array($addrlist)) {
            return;
        }

        $addr_array = array();

        foreach (Horde_Mime_Address::getAddressesFromObject($addrlist) as $ob) {
            if (!empty($ob['inner'])) {
                try {
                    $addr_array[] = array('raw' => Horde::callHook('dimp_addressformatting', array($ob), 'imp'));
                } catch (Horde_Exception_HookNotSet $e) {
                    $tmp = array('inner' => $ob['inner']);
                    if (!empty($ob['personal'])) {
                        $tmp['personal'] = $ob['personal'];
                    }
                    $addr_array[] = $tmp;
                }
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
     * 'fulldate' - The fully formatted date
     * 'minidate' - A miniature date
     *
     * FOR NON-PREVIEW MODE:
     * 'bcc' - The Bcc addresses
     * 'headers' - An array of headers (not including basic headers)
     * 'list_info' - List information.
     * 'priority' - The X-Priority of the message ('low', 'high', 'normal')
     * 'replyTo' - The Reply-to addresses
     * 'save_as' - The save link
     * 'title' - The title of the page
     * </pre>
     * @throws Horde_Exception
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
            'uid' => $uid
        );

        /* Set the current time zone. */
        Horde_Nls::setTimeZone();

        /* Get envelope/header information. We don't use flags in this
         * view. */
        try {
            $fetch_ret = $GLOBALS['imp_imap']->ob()->fetch($mailbox, array(
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => false))
            ), array('ids' => array($uid)));
        } catch (Horde_Imap_Client_Exception $e) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        /* Parse MIME info and create the body of the message. */
        try {
            $imp_contents = IMP_Contents::singleton($uid . IMP::IDX_SEP . $mailbox);
        } catch (Horde_Exception $e) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        $envelope = $fetch_ret[$uid]['envelope'];
        $mime_headers = reset($fetch_ret[$uid]['headertext']);

        /* Get the IMP_UI_Message:: object. */
        $imp_ui = new IMP_UI_Message();

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

        if (empty($result['reply-to']) ||
            ($result['from'] == $result['reply-to'])) {
            unset($result['reply-to']);
        }

        /* Build the rest of the headers. */
        foreach ($headers_list as $head => $str) {
            if (!$preview && isset($result[$head])) {
                /* JS requires camelized name for reply-to. */
                if ($head == 'reply-to') {
                    $head = 'replyTo';
                    $result[$head] = $result['reply-to'];
                    unset($result['reply-to']);
                }
                $headers[] = array('id' => Horde_String::ucfirst($head), 'name' => $str, 'value' => '');
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
                    $headers[] = array('id' => Horde_String::ucfirst($head), 'name' => $str, 'value' => $val);
                }
            }
        }

        /* Grab maillog information. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog']) &&
            ($tmp = IMP_Dimp::getMsgLogInfo($envelope['message-id']))) {
            $result['log'] = $tmp;
        }

        if ($preview) {
            /* Get minidate. */
            $imp_mailbox_ui = new IMP_UI_Mailbox();
            $minidate = $imp_mailbox_ui->getDate($envelope['date']);
            if (empty($minidate)) {
                $minidate = _("Unknown Date");
            }
            $result['localdate'] = $result['minidate'] = htmlspecialchars($minidate);
        } else {
            /* Display the user-specified headers for the current identity. */
            $user_hdrs = $imp_ui->getUserHeaders();
            foreach ($user_hdrs as $user_hdr) {
                $user_val = $mime_headers->getValue($user_hdr);
                if (!empty($user_val)) {
                    $headers[] = array('name' => $user_hdr, 'value' => htmlspecialchars($user_val));
                }
            }
            $result['headers'] = $headers;
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

        /* Get X-Priority. */
        if (!$preview) {
            $result['priority'] = $imp_ui->getXpriority($mime_headers->getValue('x-priority'));
        }

        // Create message text and attachment list.
        $parts_list = $imp_contents->getContentTypeMap();
        $atc_parts = $display_ids = array();
        $result['msgtext'] = '';

        $show_parts = $GLOBALS['prefs']->getValue('parts_display');
        if ($show_parts == 'all') {
            $atc_parts = array_keys($parts_list);
        }

        $contents_mask = IMP_Contents::SUMMARY_BYTES |
            IMP_Contents::SUMMARY_SIZE |
            IMP_Contents::SUMMARY_ICON |
            IMP_Contents::SUMMARY_DESCRIP_LINK |
            IMP_Contents::SUMMARY_DOWNLOAD |
            IMP_Contents::SUMMARY_DOWNLOAD_ZIP;

        $part_info = $part_info_display = array('icon', 'description', 'type', 'size', 'download', 'download_zip');

        /* Do MDN processing now. */
        if ($imp_ui->MDNCheck($mailbox, $uid, $mime_headers)) {
            $result['msgtext'] .= $imp_ui->formatStatusMsg(array(array('text' => array(_("The sender of this message is requesting a Message Disposition Notification from you when you have read this message."), sprintf(_("Click %s to send the notification message."), Horde::link('', '', '', '', 'DimpCore.doAction(\'SendMDN\',{folder:\'' . $mailbox . '\',uid:' . $uid . '}); return false;', '', '') . _("HERE") . '</a>')))));
        }

        /* Build body text. This needs to be done before we build the
         * attachment list that lives in the header. */
        foreach ($parts_list as $mime_id => $mime_type) {
            if (in_array($mime_id, $display_ids, true)) {
                continue;
            }


            if (!($render_mode = $imp_contents->canDisplay($mime_id, IMP_Contents::RENDER_INLINE | IMP_Contents::RENDER_INFO))) {
                if ($imp_contents->isAttachment($mime_type)) {
                    if ($show_parts == 'atc') {
                        $atc_parts[] = $mime_id;
                    }

                    if ($GLOBALS['prefs']->getValue('atc_display')) {
                        $result['msgtext'] .= $imp_ui->formatSummary($imp_contents->getSummary($mime_id, $contents_mask), $part_info_display, true);
                    }
                }
                continue;
            }

            $render_part = $imp_contents->renderMIMEPart($mime_id, $render_mode);
            if (($render_mode & IMP_Contents::RENDER_INLINE) && empty($render_part)) {
                /* This meant that nothing was rendered - allow this part to
                 * appear in the attachment list instead. */
                if ($show_parts == 'atc') {
                    $atc_parts[] = $mime_id;
                }
                continue;
            }

            reset($render_part);
            while (list($id, $info) = each($render_part)) {
                $display_ids[] = $id;

                if (empty($info)) {
                    continue;
                }

                $result['msgtext'] .= $imp_ui->formatSummary($imp_contents->getSummary($id, $contents_mask), $part_info_display) .
                    $imp_ui->formatStatusMsg($info['status']) .
                    '<div class="mimePartData">' . $info['data'] . '</div>';

                if (isset($info['js'])) {
                    $result['js'] = array_merge($result['js'], $info['js']);
                }
            }
        }

        if (!strlen($result['msgtext'])) {
            $result['msgtext'] = $imp_ui->formatStatusMsg(array(array('text' => array(_("There are no parts that can be shown inline.")))));
        }

        if (count($atc_parts) ||
            (($show_parts == 'all') && count($display_ids) > 2)) {
            $result['atc_label'] = ($show_parts == 'all')
                ? _("Parts")
                : sprintf(ngettext("%d Attachment", "%d Attachments", count($atc_parts)), count($atc_parts));
            if (count($display_ids) > 2) {
                $result['atc_download'] = Horde::link($imp_contents->urlView($imp_contents->getMIMEMessage(), 'download_all')) . '[' . _("Save All") . ']</a>';
            }
        }

        /* Show attachment information in headers? */
        if (!empty($atc_parts)) {
            $tmp = '';

            if ($show_parts == 'all') {
                array_unshift($part_info, 'id');
            }

            foreach ($atc_parts as $id) {
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
        } elseif (!$preview) {
            try {
                $result = Horde::callHook('dimp_messageview', array($result), 'imp');
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        if (!$preview) {
            $result['list_info'] = $imp_ui->getListInformation($mime_headers);
            $result['save_as'] = Horde::downloadUrl(htmlspecialchars_decode($result['subject']), array_merge(array('actionID' => 'save_message'), IMP::getIMPMboxParameters($mailbox, $uid, $mailbox)));
        }

        if (empty($result['js'])) {
            unset($result['js']);
        }

        return $result;
    }
}
