<?php
/**
 * Dimp show message view logic.
 *
 * Copyright 2005-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */
class DIMP_Views_ShowMessage
{
    /**
     * Builds a list of addresses from header information.
     *
     * @param IMP_Headers &$headers  The headers object.
     * @param array $addrlist        The list of addresses from
     *                               MIME::parseAddressList().
     *
     * @return array  Array with the following keys: address, display, inner,
     *                personal, raw.
     */
    private function _buildAddressList(&$headers, $addrlist)
    {
        if (empty($addrlist) || !is_array($addrlist)) {
            return;
        }

        $addr_array = array();
        $call_hook = !empty($GLOBALS['conf']['hooks']['addressformatting']);

        foreach (Horde_Mime_Address::getAddressesFromObject($addrlist) as $ob) {
            if (empty($ob->address) || empty($ob->inner)) {
                continue;
            }

            /* If this is an incomplete e-mail address, don't link to
             * anything. */
            if ($call_hook) {
                $result = Horde::callHook('_dimp_hook_addressformatting', array($ob), 'dimp');
                if (is_a($result, 'PEAR_Error')) {
                    Horde::logMessage($result, __FILE__, __LINE__, PEAR_LOG_ERR);
                } else {
                    $addr_array[] = array('raw' => $result);
                }
            } elseif (stristr($ob->host, 'UNKNOWN') !== false) {
                $addr_array[] = array('raw' => htmlspecialchars($ob->address));
            } else {
                $tmp = array();
                foreach (array('address', 'display', 'inner', 'personal') as $val) {
                    if ($val == 'display') {
                        $ob->display = htmlspecialchars($ob->display);
                        if ($ob->display == $ob->address) {
                            continue;
                        }
                    }
                    if (!empty($ob->$val)) {
                        $tmp[$val] = $ob->$val;
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
     * 'headers' - The headers desired in the returned headers array (only used
     *             with non-preview view)
     * 'folder' - The folder name
     * 'index' - The folder index
     * 'preview' - Is this the preview view?
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
     * 'folder' - The IMAP folder
     * 'index' - The IMAP UID
     * 'msgtext' - The text of the message
     * 'priority' - The X-Priority of the message ('low', 'high', 'normal')
     * 'to' - The To addresses
     * 'uid' - The unique UID of this message
     *
     * FOR PREVIEW MODE:
     * 'fulldate' - The fully formatted date
     * 'js' - Javascript code to run on display (only if the previewview
     *        hook is active)
     * 'minidate' - A miniature date
     *
     * FOR NON-PREVIEW MODE:
     * 'bcc' - The Bcc addresses
     * 'headers' - An array of headers (not including basic headers)
     * 'replyTo' - The Reply-to addresses
     * </pre>
     */
    public function showMessage($args)
    {
        $preview = !empty($args['preview']);
        $folder = $args['folder'];
        $index = $args['index'];
        $error_msg = _("Requested message not found.");

        $result = array(
            'folder' => $folder,
            'index' => $index,
            'uid' => $index . $folder,
        );

        /* Set the current time zone. */
        NLS::setTimeZone();

        /* Get envelope/flag/header information. */
        try {
            $fetch_ret = $GLOBALS['imp_imap']->ob->fetch($folder, array(
                Horde_Imap_Client::FETCH_ENVELOPE => true,
                Horde_Imap_Client::FETCH_FLAGS => true,
                Horde_Imap_Client::FETCH_HEADERTEXT => array(array('parse' => true, 'peek' => true))
            ), array('ids' => array($index)));
            $ob = $fetch_ret[$index];
        } catch (Horde_Imap_Client_Exception $e) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        /* Parse MIME info and create the body of the message. */
        $imp_contents = &IMP_Contents::singleton($index . IMP_IDX_SEP . $folder);
        if (is_a($imp_contents, 'PEAR_Error') ||
            !$imp_contents->buildMessage()) {
            $result['error'] = $error_msg;
            $result['errortype'] = 'horde.error';
            return $result;
        }

        /* Get the IMP_UI_Message:: object. */
        $imp_ui = new IMP_UI_Message();

        /* Update the message flag, if necessary. */
        if (($_SESSION['imp']['protocol'] == 'imap') &&
            !in_array('\\seen', $ob['flags'])) {
            $imp_mailbox = &IMP_Mailbox::singleton($folder, $index);
            $imp_message = &IMP_Message::singleton();
            $imp_message->flag(array('\\seen'), array($folder => array($index)), true);
        }

        /* Determine if we should generate the attachment strip links or
         * not. */
        if ($GLOBALS['prefs']->getValue('strip_attachments')) {
            $imp_contents->setStripLink(true);
        }

        /* Show summary links. */
        $imp_contents->showSummaryLinks(true);

        $attachments = $imp_contents->getAttachments();
        $result['msgtext'] = $imp_contents->getMessage();

        /* Develop the list of Headers to display now. Deal with the 'basic'
         * header information first since there are various manipulations
         * done to them. */
        $headers_list = $imp_ui->basicHeaders();
        if (empty($args['headers'])) {
            $args['headers'] = array('from', 'date', 'to', 'cc');
        }

        $basic_headers = array_intersect_key($headers_list, array_flip($args['headers']));

        /* Build From/To/Cc/Bcc/Reply-To links. */
        foreach (array('from', 'to', 'cc', 'bcc', 'reply-to') as $val) {
            if (isset($basic_headers[$val]) &&
                (!$preview || !in_array($val, array('bcc', 'reply-to')))) {
                $tmp = $this->_buildAddressList($imp_headers, $ob->addrlist[$val]);
                if (!empty($tmp)) {
                    $result[$val] = $tmp;
                } elseif ($val == 'to') {
                    $result[$val] = array(array('raw' => _("Undisclosed Recipients")));
                }
                if ($preview) {
                    unset($basic_headers[$val]);
                }
            }
        }

        if (empty($result['reply-to']) ||
            ($result['from'] == $result['reply-to'])) {
            unset($result['reply-to']);
        }

        /* Build the rest of the headers. */
        foreach ($basic_headers as $head => $str) {
            if (!$preview && isset($result[$head])) {
                /* JS requires camelized name for reply-to. */
                if ($head == 'reply-to') {
                    $head = 'replyTo';
                    $result[$head] = $result['reply-to'];
                    unset($result['reply-to']);
                }
                $headers[] = array('id' => String::ucfirst($head), 'name' => $str, 'value' => '');
            } elseif ($val = $imp_headers->getValue($head)) {
                if ($head == 'date') {
                    /* Add local time to date header. */
                    $val = nl2br($imp_headers->addLocalTime(htmlspecialchars($val)));
                    if ($preview) {
                        $result['fulldate'] = $val;
                    }
                } elseif (!$preview) {
                    $val = htmlspecialchars($val);
                }
                if (!$preview) {
                    $headers[] = array('id' => String::ucfirst($head), 'name' => $str, 'value' => $val);
                }
            }
        }

        /* Display the user-specified headers for the current identity. */
        if (!$preview) {
            $user_hdrs = $imp_ui->getUserHeaders();
            if (!empty($user_hdrs)) {
                $full_h = $imp_headers->getAllHeaders();
                foreach ($user_hdrs as $user_hdr) {
                    foreach ($full_h as $head => $val) {
                        if (stristr($head, $user_hdr) !== false) {
                            $headers[] = array('name' => $head, 'value' => htmlspecialchars($val));
                        }
                    }
                }
            }
            $result['headers'] = $headers;
        }

        /* Process the subject. */
        if (($subject = $imp_headers->getValue('subject'))) {
            require_once 'Horde/Text.php';
            $subject = Text::htmlSpaces(IMP::filterText($subject));
        } else {
            $subject = htmlspecialchars(_("[No Subject]"));
        }
        $result['subject'] = $subject;

        /* Get X-Priority/ */
        $result['priority'] = $imp_headers->getXpriority();


        /* Add attachment info. */
        $atc_display = $GLOBALS['prefs']->getValue('attachment_display');
        $show_parts = (!empty($attachments) && (($atc_display == 'list') || ($atc_display == 'both')));
        $downloadall_link = $imp_contents->getDownloadAllLink();

        if ($attachments && ($show_parts || $downloadall_link)) {
            $result['atc_label'] = sprintf(ngettext("%d Attachment", "%d Attachments",
                                         $imp_contents->attachmentCount()),
                                         $imp_contents->attachmentCount());
            $result['atc_download'] = ($downloadall_link) ? Horde::link($downloadall_link) . _("Save All") . '</a>' : '';
        }
        if ($show_parts) {
            $result['atc_list'] = $attachments;
        }

        if ($preview) {
            $curr_time = time();
            $curr_time -= $curr_time % 60;
            $ltime_val = localtime();
            $today_start = mktime(0, 0, 0, $ltime_val[4] + 1, $ltime_val[3], 1900 + $ltime_val[5]);
            $today_end = $today_start + 86400;
            if (empty($ob->date)) {
                $udate = false;
            } else {
                $ob->date = preg_replace('/\s+\(\w+\)$/', '', $ob->date);
                $udate = strtotime($ob->date, $curr_time);
            }
            if ($udate === false || $udate === -1) {
                $result['minidate'] = _("Unknown Date");
            } elseif (($udate < $today_start) || ($udate > $today_end)) {
                /* Not today, use the date. */
                $result['minidate'] = strftime($GLOBALS['prefs']->getValue('date_format'), $udate);
            } else {
                /* Else, it's today, use the time. */
                $result['minidate'] = strftime($GLOBALS['prefs']->getValue('time_format'), $udate);
            }
        }

        if ($preview && !empty($GLOBALS['conf']['hooks']['previewview'])) {
            $res = Horde::callHook('_dimp_hook_previewview', array($result), 'dimp');
            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $result = $res[0];
                $result['js'] = $res[1];
            }
        } elseif (!$preview && !empty($GLOBALS['conf']['hooks']['messageview'])) {
            $res = Horde::callHook('_dimp_hook_messageview', array($result), 'dimp');
            if (is_a($res, 'PEAR_Error')) {
                Horde::logMessage($res, __FILE__, __LINE__, PEAR_LOG_ERR);
            } else {
                $result = $res;
            }
        }

        /* Retrieve any history information for this message. */
        if (!empty($GLOBALS['conf']['maillog']['use_maillog'])) {
            if (!$preview) {
                IMP_Maillog::displayLog($imp_headers->getValue('message-id'));
            }

            /* Do MDN processing now. */
            if ($imp_ui->MDNCheck($ob->header)) {
                $confirm_link = Horde::link('', '', '', '', 'DimpCore.doAction(\'SendMDN\',{folder:\'' . $folder . '\',index:' . $index . '}); return false;', '', '') . _("HERE") . '</a>';
                $GLOBALS['notification']->push(sprintf(_("The sender of this message is requesting a Message Disposition Notification from you when you have read this message. Click %s to send the notification message."), $confirm_link), 'dimp.request', array('content.raw'));
            }
        }

        return $result;
    }
}
