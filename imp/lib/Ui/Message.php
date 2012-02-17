<?php
/**
 * The IMP_Ui_Message:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the message page.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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
     * @param IMP_Mailbox $mailbox         The mailbox of the message.
     * @param integer $uid                 The UID of the message.
     * @param Horde_Mime_Headers $headers  The headers of the message.
     * @param boolean $confirmed           Has the MDN request been confirmed?
     *
     * @return boolean  True if the MDN request needs to be confirmed.
     */
    public function MDNCheck(IMP_Mailbox $mailbox, $uid, $headers,
                             $confirmed = false)
    {
        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();
        $pref_val = $GLOBALS['prefs']->getValue('send_mdn');

        if (!$pref_val || $mailbox->readonly) {
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
        /* 1st test: MDNSent keyword (RFC 3503 [3.1]). */
        if ($mailbox->permflags->allowed('$mdnsent')) {
            $mdn_flag = true;

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->flags();

            try {
                $res = $imp_imap->fetch($mailbox, $query, array(
                    'ids' => $imp_imap->getIdsOb($uid)
                ));
                $mdn_sent = in_array('$mdnsent', $res[$uid]->getFlags());
            } catch (IMP_Imap_Exception $e) {}
        } else {
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
            IMP_Maillog::log(IMP_Maillog::MDN, $msg_id, 'displayed');
            $success = true;

            if ($mdn_flag) {
                $GLOBALS['injector']->getInstance('IMP_Message')->flag(array(Horde_Imap_Client::FLAG_MDNSENT), $mailbox->getIndicesOb($uid), true);
            }
        } catch (Exception $e) {
            $success = false;
        }

        $GLOBALS['injector']->getInstance('IMP_Sentmail')->log(IMP_Sentmail::MDN, '', $return_addr, $success);

        return false;
    }

    /**
     * Adds the local time string to the date header.
     *
     * @param Horde_Imap_Client_DateTime $date  The date object.
     *
     * @return string  The local formatted time string.
     */
    public function getLocalTime(Horde_Imap_Client_DateTime $date)
    {
        $time_str = strftime($GLOBALS['prefs']->getValue('time_format'), strval($date));
        $tz = strftime('%Z');

        if ((date('Y') != $date->format('Y')) ||
            (date('M') != $date->format('M')) ||
            (date('d') != $date->format('d'))) {
            /* Not today, use the date. */
            $date_str = strftime($GLOBALS['prefs']->getValue('date_format'), strval($date));
            return sprintf('%s (%s %s)', $date_str, $time_str, $tz);
        }

        /* Else, it's today, use the time only. */
        return sprintf(_("Today, %s %s"), $time_str, $tz);
    }

    /**
     * Parses all of the available mailing list headers.
     *
     * @param Horde_Mime_Headers $headers  A Horde_Mime_Headers object.
     *
     * @return array  Keys are the list header names, values are the
     *                parsed list header values.
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
                if (!empty($opts['email'])) {
                    continue;
                }

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
        $mimp_view = IMP::getViewMode() == 'mimp';

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
                            '<span class="iconImg addrbookaddImg"></span></a>';
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
                            '<span class="iconImg addrbookaddImg"></span></a>';
                    }
                }

                $addr_array[] = $ret;
            }
        }

        if (IMP::getViewMode() == 'mimp') {
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
     * Increment mailbox index after deleting a message?
     *
     * @return boolean  If true, increments index.
     */
    public function moveAfterAction()
    {
        return (!IMP::$mailbox->hideDeletedMsgs() &&
                !$GLOBALS['prefs']->getValue('use_trash'));
    }

}
