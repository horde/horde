<?php
/**
 * The IMP_Ui_Message:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the message page.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ui_Message
{
    /**
     * Return a list of "basic" headers w/gettext translations.
     *
     * @return array  Header name -> gettext translation mapping.
     */
    public function basicHeaders()
    {
        return array(
            'date'      =>  _("Date"),
            'from'      =>  _("From"),
            'to'        =>  _("To"),
            'cc'        =>  _("Cc"),
            'bcc'       =>  _("Bcc"),
            'reply-to'  =>  _("Reply-To"),
            'subject'   =>  _("Subject")
        );
    }

    /**
     * Get the list of user-defined headers to display.
     *
     * @return array  The list of user-defined headers.
     */
    public function getUserHeaders()
    {
        $user_hdrs = $GLOBALS['prefs']->getValue('mail_hdr');

        /* Split the list of headers by new lines and sort the list of headers
         * to make sure there are no duplicates. */
        if (is_array($user_hdrs)) {
            $user_hdrs = implode("\n", $user_hdrs);
        }
        $user_hdrs = trim($user_hdrs);
        if (empty($user_hdrs)) {
            return array();
        }

        $user_hdrs = array_filter(array_keys(array_flip(array_map('trim', preg_split("/[\n\r]+/", str_replace(':', '', $user_hdrs))))));
        natcasesort($user_hdrs);

        return $user_hdrs;
    }

    /**
     * Check if we need to send a MDN, and send if needed.
     *
     * @param string $mailbox              The mailbox of the message.
     * @param integer $uid                 The UID of the message.
     * @param Horde_Mime_Headers $headers  The headers of the message.
     * @param boolean $confirmed           Has the MDN request been confirmed?
     *
     * @return boolean  True if the MDN request needs to be confirmed.
     */
    public function MDNCheck($mailbox, $uid, $headers, $confirmed = false)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $pref_val = $GLOBALS['prefs']->getValue('send_mdn');

        if (!$pref_val || $imp_imap->isReadOnly($mailbox)) {
            return false;
        }

        /* Check to see if an MDN has been requested. */
        $mdn = new Horde_Mime_Mdn($headers);
        $return_addr = $mdn->getMdnReturnAddr();
        if (!$return_addr) {
            return false;
        }

        $msg_id = $headers->getValue('message-id');
        $mdn_flag = $mdn_sent = false;

        /* See if we have already processed this message. */
        /* 1st test: $MDNSent keyword (RFC 3503 [3.1]). */
        try {
            $status = $imp_imap->status($mailbox, Horde_Imap_Client::STATUS_PERMFLAGS);
            if (in_array('\\*', $status['permflags']) ||
                in_array('$mdnsent', $status['permflags'])) {
                $mdn_flag = true;
                $res = $imp_imap->fetch($mailbox, array(
                        Horde_Imap_Client::FETCH_FLAGS => true
                    ), array('ids' => array($uid)));
                $mdn_sent = in_array('$mdnsent', $res[$uid]['flags']);
            }
        } catch (Horde_Imap_Client_Exception $e) {}

        if (!$mdn_flag) {
            /* 2nd test: Use Maillog as a fallback. */
            $mdn_sent = IMP_Maillog::sentMDN($msg_id, 'displayed');
        }

        if ($mdn_sent) {
            return false;
        }

        /* See if we need to query the user. */
        if (!$confirmed &&
            ((intval($pref_val) == 1) ||
             $mdn->userConfirmationNeeded())) {
            try {
                if (Horde::callHook('mdn_check', array($headers), 'imp')) {
                    return true;
                }
            } catch (Horde_Exception_HookNotSet $e) {
                return true;
            }
        }

        /* Send out the MDN now. */
        try {
            $mdn->generate(
                false,
                $confirmed,
                'displayed',
                $GLOBALS['conf']['server']['name'],
                $GLOBALS['injector']->getInstance('IMP_Mail'),
                array(
                    'charset' => 'UTF-8',
                    'from_addr' => $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create()->getDefaultFromAddress()
                )
            );
            IMP_Maillog::log('mdn', $msg_id, 'displayed');
            $success = true;

            if ($mdn_flag) {
                $GLOBALS['injector']->getInstance('IMP_Message')->flag(array('$MDNSent'), new IMP_Indices($mailbox, $uid), true);
            }
        } catch (Exception $e) {
            $success = false;
        }

        $GLOBALS['injector']->getInstance('IMP_Sentmail')->log('mdn', '', $return_addr, $success);

        return false;
    }

    /**
     * Adds the local time string to the date header.
     *
     * @param string $date  The date string.
     *
     * @return string  The local formatted time string.
     */
    public function getLocalTime($date)
    {
        if (empty($date)) {
            $ltime = false;
        } else {
            $date = preg_replace('/\s+\(\w+\)$/', '', $date);
            $ltime = strtotime($date);
        }

        if (($ltime === false) || ($ltime === -1)) {
            return '';
        }

        $time_str = strftime($GLOBALS['prefs']->getValue('time_format'), $ltime);
        $tz = strftime('%Z');

        if ((date('Y') != @date('Y', $ltime)) ||
            (date('M') != @date('M', $ltime)) ||
            (date('d') != @date('d', $ltime))) {
            /* Not today, use the date. */
            $date_str = strftime($GLOBALS['prefs']->getValue('date_format'), $ltime);
            return sprintf('%s %s %s', $date_str, $time_str, $tz);
        }

        /* Else, it's today, use the time only. */
        return sprintf(_("Today, %s %s"), $time_str, $tz);
    }

    /**
     * Parses all of the available mailing list headers.
     *
     * @param Horde_Mime_Headers $headers  A Horde_Mime_Headers object.
     *
     * @return array  TODO
     */
    public function parseAllListHeaders($headers)
    {
        $ret = array();

        foreach (array_keys($headers->listHeaders()) as $val) {
            if (($data = $headers->getValue($val))) {
                $ret[$val] = $this->parseListHeaders($data);
            }
        }

        return $ret;
    }

    /**
     * Parse the information in mailing list headers.
     *
     * @param string $data  The header text to process.
     * @param array $opts   Additional options:
     * <pre>
     * 'email' - (boolean) Only return e-mail values.
     *           DEFAULT: false
     * 'raw' - (boolean) Should the raw URL be returned instead of linking
     *                   the header value?
     *                   DEFAULT: false
     * </pre>
     *
     * @return string  The header value.
     */
    public function parseListHeaders($data, $opts = array())
    {
        $output = '';

        /* Split the incoming data by the ',' character. */
        foreach (explode(',', $data) as $orig_entry) {
            $entry = Horde_Mime_Address::trimAddress($orig_entry);

            /* Get the data inside of the brackets. If there is no brackets,
             * then return the raw text. */
            if (trim($orig_entry) == $entry) {
                return $entry;
            }

            /* Remove all whitespace from between brackets (RFC 2369 [2]). */
            $match = preg_replace("/\s+/", '', $entry);

            /* Determine if there are any comments. */
            preg_match("/(\(.+\))/", $entry, $comments);

            /* RFC 2369 [2] states that we should only show the *FIRST* URL
             * that appears in a header that we can adequately handle. */
            if (stristr($match, 'mailto:') !== false) {
                $match = substr($match, strpos($match, ':') + 1);
                if (!empty($opts['raw'])) {
                    return $match;
                }
                $output = Horde::link(IMP::composeLink($match)) . $match . '</a>';
                if (!empty($comments[1])) {
                    $output .= '&nbsp;' . $comments[1];
                }

                return $output;
            } elseif ($url = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($match, 'linkurls')) {
                if (!empty($opts['raw'])) {
                    return $match;
                }

                $output = $url;
                if (!empty($comments[1])) {
                    $output .= '&nbsp;' . $comments[1];
                }

                return $output;
            } else {
                /* Use this entry unless we can find a better one. */
                $output = $match;
            }
        }

        return $output;
    }

    /**
     * Returns e-mail information for a mailing list.
     *
     * @param Horde_Mime_Headers $headers  A Horde_Mime_Headers object.
     *
     * @return array  An array with 2 elements: 'exists' and 'reply_list'.
     */
    public function getListInformation($headers)
    {
        $ret = array('exists' => false, 'reply_list' => null);

        if ($headers->listHeadersExist()) {
            $ret['exists'] = true;

            /* See if the List-Post header provides an e-mail address for the
             * list. */
            if (($val = $headers->getValue('list-post')) &&
                ($val != 'NO')) {
                $ret['reply_list'] = $this->parseListHeaders($val, array('email' => true, 'raw' => true));
            }
        }

        return $ret;
    }

    /**
     * Builds a string containing a list of addresses.
     *
     * @param array $addrlist    The list of addresses from
     *                           Horde_Mime_Address::parseAddressList().
     * @param Horde_Url $addURL  The self URL.
     * @param boolean $link      Link each address to the compose screen?
     *
     * @return string  String containing the formatted address list.
     */
    public function buildAddressLinks($addrlist, $addURL = null, $link = true)
    {
        global $prefs, $registry;

        /* Make sure this is a valid object address field. */
        if (empty($addrlist) || !is_array($addrlist)) {
            return null;
        }

        $add_link = null;
        $addr_array = array();
        $mimp_view = ($GLOBALS['session']->get('imp', 'view') == 'mimp');

        /* Set up the add address icon link if contact manager is
         * available. */
        if (!is_null($addURL) && $link && $prefs->getValue('add_source')) {
            try {
                $add_link = $registry->hasMethod('contacts/import')
                    ? $addURL->copy()->add('actionID', 'add_address')
                    : null;
            } catch (Horde_Exception $e) {}
        }

        foreach (Horde_Mime_Address::getAddressesFromObject($addrlist, array('charset' => 'UTF-8')) as $ob) {
            if (isset($ob['groupname'])) {
                $group_array = array();
                foreach ($ob['addresses'] as $ad) {
                    if (empty($ad['address']) || empty($ad['inner'])) {
                        continue;
                    }

                    $ret = $mimp_view
                        ? $ad['display']
                        : htmlspecialchars($ad['display']);

                    if ($link) {
                        $ret = Horde::link(IMP::composeLink(array('to' => $ad['address'])), sprintf(_("New Message to %s"), $ad['inner'])) . htmlspecialchars($ad['display']) . '</a>';
                    }

                    /* Append the add address icon to every address if contact
                     * manager is available. */
                    if ($add_link) {
                        $curr_link = $add_link->copy()->add(array('name' => $ad['personal'], 'address' => $ad['inner']));
                        $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ad['inner'])) .
                            Horde::img('addressbook_add.png', sprintf(_("Add %s to my Address Book"), $ad['inner'])) . '</a>';
                    }

                    $group_array[] = $ret;
                }

                if (!$mimp_view) {
                    $ob['groupname'] = htmlspecialchars($ob['groupname']);
                }

                $addr_array[] = $ob['groupname'] . ':' . (count($group_array) ? ' ' . implode(', ', $group_array) : '');
            } elseif (!empty($ob['address']) && !empty($ob['inner'])) {
                $ret = $mimp_view
                    ? $ob['display']
                    : htmlspecialchars($ob['display']);

                /* If this is an incomplete e-mail address, don't link to
                 * anything. */
                if (stristr($ob['host'], 'UNKNOWN') === false) {
                    if ($link) {
                        $ret = Horde::link(IMP::composeLink(array('to' => $ob['address'])), sprintf(_("New Message to %s"), $ob['inner'])) . htmlspecialchars($ob['display']) . '</a>';
                    }

                    /* Append the add address icon to every address if contact
                     * manager is available. */
                    if ($add_link) {
                        $curr_link = $add_link->copy()->add(array('name' => $ob['personal'], 'address' => $ob['inner']));
                        $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ob['inner'])) .
                            Horde::img('addressbook_add.png', sprintf(_("Add %s to my Address Book"), $ob['inner'])) . '</a>';
                    }
                }

                $addr_array[] = $ret;
            }
        }

        if ($GLOBALS['session']->get('imp', 'view') == 'mimp') {
            return implode(', ', $addr_array);
        }

        /* If left with an empty address list ($ret), inform the user that the
         * recipient list is purposely "undisclosed". */
        if (empty($addr_array)) {
            $ret = _("Undisclosed Recipients");
        } else {
            /* Build the address line. */
            $addr_count = count($addr_array);
            $ret = '<span class="nowrap">' . implode(',</span> <span class="nowrap">', $addr_array) . '</span>';
            if ($link && $addr_count > 15) {
                $ret = '<span>' .
                    '<span onclick="[ this, this.next(), this.next(1) ].invoke(\'toggle\')" class="widget largeaddrlist">' . sprintf(_("[Show Addresses - %d recipients]"), $addr_count) . '</span>' .
                    '<span onclick="[ this, this.previous(), this.next() ].invoke(\'toggle\')" class="widget largeaddrlist" style="display:none">' . _("[Hide Addresses]") . '</span>' .
                    '<span style="display:none">' .
                    $ret . '</span></span>';
            }
        }

        return $ret;
    }

    /**
     * Prints out a MIME summary (in HTML).
     *
     * @param array $summary  Summary information from
     *                        IMP_Contents::getSummary().
     * @param array $display  The fields to display (in this order).
     * @param boolean $atc    Is this an attachment?
     *
     * @return string  The formatted summary string.
     */
    public function formatSummary($summary, $display, $atc = false)
    {
        $tmp_summary = array();
        foreach ($display as $val) {
            if (isset($summary[$val])) {
                $tmp_summary[] = $summary[$val];
            }
        }
        return '<div class="mimePartInfo' . ($atc ? ' mimePartInfoAtc' : '') . '"><div>' . implode(' ', $tmp_summary) . '</div></div>';
    }

    /**
     * Prints out a MIME status message (in HTML).
     *
     * @param array $data  An array of information (as returned from
                           Horde_Mime_Viewer_Base::render()).
     *
     * @return string  The formatted status message string.
     */
    public function formatStatusMsg($data)
    {
        $out = '';

        foreach ($data as $val) {
            if (empty($val)) {
                continue;
            }

            $out .= '<div><table class="mimeStatusMessageTable"' . (isset($val['id']) ? (' id="' . $val['id'] . '" ') : '') . '>';

            /* If no image, simply print out the message. */
            if (empty($val['icon'])) {
                foreach ($val['text'] as $val) {
                    $out .= '<tr><td>' . $val . '</td></tr>';
                }
            } else {
                $out .= '<tr><td class="mimeStatusIcon">' . $val['icon'] . '</td><td><table>';
                foreach ($val['text'] as $val) {
                    $out .= '<tr><td>' . $val . '</td></tr>';
                }
                $out .= '</table></td></tr>';
            }

            $out .= '</table></div>';
        }

        return $out
            ? '<div class="mimeStatusMessage">' . $out . '</div>'
            : '';
    }

    /**
     * Generate inline message display.
     *
     * @param object $imp_contents  The IMP_Contents object containing the
     *                              message data.
     * @param array $options        Additional options:
     * <pre>
     * 'display_mask' - (integer) The mask of display view type to render
     *                  inline (DEFAULT: IMP_Contents::RENDER_INLINE_AUTO).
     * 'mask' - (integer) The mask needed for a IMP_Contents::getSummary()
     *          call.
     * 'no_inline_all' - (boolean) If true, only display first inline part.
     *                   Subsequent inline parts will be treated as
     *                   attachments.
     * 'part_info_display' - (array) The list of summary fields to display.
     * 'show_parts' - (string) The value of the 'parts_display' pref.
     * </pre>
     *
     * @return array  An array with the following keys:
     * <pre>
     * 'atc_parts' - (array) The list of attachment MIME IDs.
     * 'display_ids' - (array) The list of display MIME IDs.
     * 'js_onload' - (array) A list of javascript code to run onload.
     * 'msgtext' - (string) The rendered HTML code.
     * </pre>
     */
    public function getInlineOutput($imp_contents, $options = array())
    {
        $atc_parts = $display_ids = $js_onload = $wrap_ids = array();
        $msgtext = array();
        $parts_list = $imp_contents->getContentTypeMap();
        $text_out = '';

        $contents_mask = isset($options['mask'])
            ? $options['mask']
            : 0;
        $display_mask = isset($options['display_mask'])
            ? $options['display_mask']
            : IMP_Contents::RENDER_INLINE_AUTO;
        $no_inline_all = !empty($options['no_inline_all']);
        $part_info_display = isset($options['part_info_display'])
            ? $options['part_info_display']
            : array();
        $show_parts = isset($options['show_parts'])
            ? $options['show_parts']
            : $GLOBALS['prefs']->getValue('parts_display');

        foreach ($parts_list as $mime_id => $mime_type) {
            if (isset($display_ids[$mime_id]) ||
                isset($atc_parts[$mime_id])) {
                continue;
            }

            if (!($render_mode = $imp_contents->canDisplay($mime_id, $display_mask))) {
                if ($imp_contents->isAttachment($mime_type)) {
                    if ($show_parts == 'atc') {
                        $atc_parts[$mime_id] = 1;
                    }

                    if ($contents_mask && empty($info['nosummary'])) {
                        $msgtext[$mime_id] = array(
                            'text' => $this->formatSummary($imp_contents->getSummary($mime_id, $contents_mask), $part_info_display, true)
                        );
                    }
                }
                continue;
            }

            $render_part = $imp_contents->renderMIMEPart($mime_id, $render_mode);
            if (($show_parts == 'atc') &&
                $imp_contents->isAttachment($mime_type) &&
                (empty($render_part) ||
                 !($render_mode & IMP_Contents::RENDER_INLINE))) {
                $atc_parts[$mime_id] = 1;
            }

            if (empty($render_part)) {
                if ($contents_mask &&
                    empty($info['nosummary']) &&
                    $imp_contents->isAttachment($mime_type)) {
                    $msgtext[$mime_id] = array(
                        'text' => $this->formatSummary($imp_contents->getSummary($mime_id, $contents_mask), $part_info_display, true)
                    );
                }
                continue;
            }

            reset($render_part);
            while (list($id, $info) = each($render_part)) {
                $display_ids[$id] = 1;

                if (empty($info)) {
                    continue;
                }

                if ($no_inline_all === 1) {
                    $atc_parts[$id] = 1;
                    continue;
                }

                $part_text = '';

                if (empty($info['attach'])) {
                    if ($contents_mask) {
                        if (empty($info['nosummary'])) {
                            $part_text .= $this->formatSummary($imp_contents->getSummary($id, $contents_mask), $part_info_display);
                        }

                        $part_text .= $this->formatStatusMsg($info['status']) .
                            '<div class="mimePartData">' . $info['data'] . '</div>';
                    } else {
                        if ($part_text && !empty($options['sep'])) {
                            $part_text .= $options['sep'];
                        }
                        $part_text .= $info['data'];
                    }
                } else {
                    if ($show_parts == 'atc') {
                        $atc_parts[$id] = 1;
                    }

                    if ($contents_mask && empty($info['nosummary'])) {
                        $part_text .= $this->formatSummary($imp_contents->getSummary($id, $contents_mask), $part_info_display, true);
                    }
                }

                $msgtext[$id] = array(
                    'text' => $part_text,
                    'wrap' => empty($info['wrap']) ? null : $info['wrap']
                );

                if (isset($info['js'])) {
                    $js_onload = array_merge($js_onload, $info['js']);
                }

                if ($no_inline_all) {
                    $no_inline_all = 1;
                }
            }
        }

        if (!empty($msgtext)) {
            uksort($msgtext, 'strnatcmp');
        }

        reset($msgtext);
        while (list($id, $part) = each($msgtext)) {
            while (count($wrap_ids) &&
                   !Horde_Mime::isChild(end($wrap_ids), $id)) {
                array_pop($wrap_ids);
                $text_out .= '</div>';
            }

            if (!empty($part['wrap'])) {
                $text_out .= '<div class="' . $part['wrap'] . '">';
                $wrap_ids[] = $id;
            }

            $text_out .= $part['text'];
        }

        $text_out .= str_repeat('</div>', count($wrap_ids));

        if (!strlen($text_out)) {
            $text_out = $this->formatStatusMsg(array(array('text' => array(_("There are no parts that can be shown inline.")))));
        }

        $atc_parts = ($show_parts == 'all')
            ? array_diff(array_keys($atc_parts), array_keys($display_ids))
            : array_keys($atc_parts);

        return array(
            'atc_parts' => $atc_parts,
            'display_ids' => array_keys($display_ids),
            'js_onload' => $js_onload,
            'msgtext' => $text_out
        );
    }

    /**
     * Get the display subject (filtered, formatted, and linked).
     *
     * @param string $subject  The subject text.
     *
     * @return string  The display subject string.
     */
    public function getDisplaySubject($subject)
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(preg_replace("/\b\s+\b/", ' ', IMP::filterText($subject)), 'text2html', array(
            'parselevel' => Horde_Text_Filter_Text2html::MICRO
        ));
    }

    /**
     * Redirect to mailbox after deleting a message?
     *
     * @return boolean  Return to mailbox?
     */
    public function moveAfterAction()
    {
        return (($GLOBALS['session']->get('imp', 'protocol') != 'pop') &&
                !IMP::hideDeletedMsgs(IMP::$mailbox) &&
                !$GLOBALS['prefs']->getValue('use_trash'));
    }

}
