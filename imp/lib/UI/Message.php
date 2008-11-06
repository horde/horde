<?php
/**
 * The IMP_UI_Message:: class is designed to provide a place to dump common
 * code shared among IMP's various UI views for the message page.
 *
 * $Horde: imp/lib/UI/Message.php,v 1.16 2008/06/26 18:58:20 slusarz Exp $
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 * @since   IMP 4.2
 */
class IMP_UI_Message
{
    /**
     */
    function basicHeaders()
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
     * @return array  TODO
     */
    function getUserHeaders()
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
     */
    function MDNCheck($headers, $confirmed = false)
    {
        if (!$GLOBALS['prefs']->getValue('disposition_send_mdn')) {
            return;
        }

        /* Check to see if an MDN has been requested. */
        $mdn = new Horde_MIME_MDN($headers);
        if ($mdn->getMDNReturnAddr()) {
            require_once IMP_BASE . '/lib/Maillog.php';
            $msg_id = $headers->getValue('message-id');

            /* See if we have already processed this message. */
            if (!IMP_Maillog::sentMDN($msg_id, 'displayed')) {
                /* See if we need to query the user. */
                if ($mdn->userConfirmationNeeded() && !$confirmed) {
                    return true;
                } else {
                    /* Send out the MDN now. */
                    $result = $mdn->generate(false, $confirmed, 'displayed');
                    if (!is_a($result, 'PEAR_Error')) {
                        IMP_Maillog::log('mdn', $msg_id, 'displayed');
                    }
                    if ($GLOBALS['conf']['sentmail']['driver'] != 'none') {
                        require_once IMP_BASE . '/lib/Sentmail.php';
                        $sentmail = IMP_Sentmail::factory();
                        $sentmail->log('mdn', '', $mdn->getMDNReturnAddr(), !is_a($result, 'PEAR_Error'));
                    }
                }
            }
        }

        return false;
    }

    /**
     * Adds the local time string to the date header.
     *
     * @param string $date  The date string.
     *
     * @return string  The date string with the local time added on. The
     *                 output has been run through htmlspecialchars().
     */
    function addLocalTime($date)
    {
        if (empty($date)) {
            $ltime = false;
        } else {
            $date = preg_replace('/\s+\(\w+\)$/', '', $date);
            $ltime = strtotime($date);
        }

        $date = htmlspecialchars($date);

        if ($ltime !== false && $ltime !== -1) {
            $date_str = strftime($GLOBALS['prefs']->getValue('date_format'), $ltime);
            $time_str = strftime($GLOBALS['prefs']->getValue('time_format'), $ltime);
            $tz = strftime('%Z');
            if ((date('Y') != @date('Y', $ltime)) ||
                (date('M') != @date('M', $ltime)) ||
                (date('d') != @date('d', $ltime))) {
                /* Not today, use the date. */
                $date .= ' <small>' . htmlspecialchars(sprintf('[%s %s %s]', $date_str, $time_str, $tz)) . '</small>';
            } else {
                /* Else, it's today, use the time only. */
                $date .= ' <small>' . htmlspecialchars(sprintf('[%s %s]', $time_str, $tz)) . '</small>';
            }
        }

        return $date;
    }

    /**
     * Parses all of the available mailing list headers.
     *
     * @param Horde_MIME_Headers $headers  A Horde_MIME_Headers object.
     *
     * @return array  TODO
     */
    function parseAllListHeaders($headers)
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
     * Parse the information in a mailing list headers.
     *
     * @param string $data  The header text to process.
     * @param boolean $raw  Should the raw URL be returned instead of linking
     *                      the header value?
     *
     * @return string  The header value.
     */
    function parseListHeaders($data, $raw = false)
    {
        $output = '';

        require_once 'Horde/Text.php';

        /* Split the incoming data by the ',' character. */
        foreach (preg_split("/,/", $data) as $entry) {
            /* Get the data inside of the brackets. If there is no brackets,
             * then return the raw text. */
            if (!preg_match("/\<([^\>]+)\>/", $entry, $matches)) {
                return trim($entry);
            }

            /* Remove all whitespace from between brackets (RFC 2369 [2]). */
            $match = preg_replace("/\s+/", '', $matches[1]);

            /* Determine if there are any comments. */
            preg_match("/(\(.+\))/", $entry, $comments);

            /* RFC 2369 [2] states that we should only show the *FIRST* URL
             * that appears in a header that we can adequately handle. */
            if (stristr($match, 'mailto:') !== false) {
                $match = substr($match, strpos($match, ':') + 1);
                if ($raw) {
                    return $match;
                }
                $output = Horde::link(IMP::composeLink($match)) . $match . '</a>';
                if (!empty($comments[1])) {
                    $output .= '&nbsp;' . $comments[1];
                }
                break;
            } else {
                require_once 'Horde/Text/Filter.php';
                if ($url = Text_Filter::filter($match, 'linkurls', array('callback' => 'Horde::externalUrl'))) {
                    if ($raw) {
                        return $match;
                    }
                    $output = $url;
                    if (!empty($comments[1])) {
                        $output .= '&nbsp;' . $comments[1];
                    }
                    break;
                } else {
                    /* Use this entry unless we can find a better one. */
                    $output = $match;
                }
            }
        }

        return $output;
    }

    /**
     * Determines the X-Priority of the message based on the headers.
     *
     * @param Horde_MIME_Headers $headers  Horde_MIME_Headers object.
     *
     * @return string  'high', 'low', or 'normal'.
     */
    function getXpriority($headers)
    {
        if (($priority = $headers->getValue('x-priority')) &&
            preg_match('/\s*(\d+)\s*/', $priority, $matches)) {
            if (in_array($matches[1], array(1, 2))) {
                return 'high';
            } elseif (in_array($matches[1], array(4, 5))) {
                return 'low';
            }
        }

        return 'normal';
    }

    /**
     * Returns e-mail information for a mailing list.
     *
     * @param Horde_MIME_Headers $headers  A Horde_MIME_Headers object.
     *
     * @return array  An array with 2 elements: 'exists' and 'reply_list'.
     */
    function getListInformation($headers)
    {
        $ret = array('exists' => false, 'reply_list' => null);

        if ($headers->listHeadersExist()) {
            $ret['exists'] = true;

            /* See if the List-Post header provides an e-mail address for the
             * list. */
            if (($val = $headers->getValue('list-post'))) {
                $ret['reply_list'] = $this->parseListHeaders($val, true);
            }
        }

        return $ret;
    }

    /**
     * Builds a string containing a list of addresses.
     *
     * @param array $addrlist  The list of addresses from
     *                         Horde_MIME_Address::parseAddressList().
     * @param integer $addURL  The self URL.
     * @param boolean $link    Link each address to the compose screen?
     *
     * @return string  String containing the formatted address list.
     */
    function buildAddressLinks($addrlist, $addURL, $link = true)
    {
        global $prefs, $registry;

        $add_link = null;

        /* Make sure this is a valid object address field. */
        if (empty($addrlist) || !is_array($addrlist)) {
            return null;
        }

        /* Set up the add address icon link if contact manager is
         * available. */
        if ($link && $prefs->getValue('add_source')) {
            $add_link = $registry->link('contacts/add', array('source' => $prefs->getValue('add_source')));
            if (is_a($add_link, 'PEAR_Error')) {
                if ($registry->hasMethod('contacts/import')) {
                    $add_link = Util::addParameter($addURL, 'actionID', 'add_address');
                } else {
                    $add_link = null;
                }
            }
        }

        $addr_array = array();

        foreach (Horde_MIME_Address::getAddressesFromObject($addrlist) as $ob) {
            if (isset($ob['groupname'])) {
                $group_array = array();
                foreach ($ob['addresses'] as $ad) {
                    if (empty($ad->address) || empty($ad->inner)) {
                        continue;
                    }

                    $ret = htmlspecialchars($ad->display);

                    /* If this is an incomplete e-mail address, don't link to
                     * anything. */
                    if (stristr($ad->host, 'UNKNOWN') === false) {
                        if ($link) {
                            $ret = Horde::link(IMP::composeLink(array('to' => $ad->address)), sprintf(_("New Message to %s"), $ad->inner)) . htmlspecialchars($ad->display) . '</a>';
                        }

                        /* Append the add address icon to every address if contact
                         * manager is available. */
                        if ($add_link) {
                            $curr_link = Util::addParameter($add_link, array('name' => $ad->personal, 'address' => $ad->inner));
                            $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ad->inner)) .
                                Horde::img('addressbook_add.png', sprintf(_("Add %s to my Address Book"), $ad->inner)) . '</a>';
                        }
                    }

                    $group_array[] = $ret;
                }

                $addr_array[] = htmlspecialchars($ob['groupname']) . ':' . (count($group_array) ? ' ' . implode(', ', $group_array) : '');
            } elseif (!empty($ob['address']) && !empty($ob['inner'])) {
                $ret = htmlspecialchars($ob['display']);

                /* If this is an incomplete e-mail address, don't link to
                 * anything. */
                if (stristr($ob['host'], 'UNKNOWN') === false) {
                    if ($link) {
                        $ret = Horde::link(IMP::composeLink(array('to' => $ob['address'])), sprintf(_("New Message to %s"), $ob['inner'])) . htmlspecialchars($ob['display']) . '</a>';
                    }

                    /* Append the add address icon to every address if contact
                     * manager is available. */
                    if ($add_link) {
                        $curr_link = Util::addParameter($add_link, array('name' => $ob['personal'], 'address' => $ob['inner']));
                        $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ob['inner'])) .
                            Horde::img('addressbook_add.png', sprintf(_("Add %s to my Address Book"), $ob['inner'])) . '</a>';
                    }
                }

                $addr_array[] = $ret;
            }
        }

        /* If left with an empty address list ($ret), inform the user that the
         * recipient list is purposely "undisclosed". */
        if (empty($addr_array)) {
            $ret = _("Undisclosed Recipients");
        } else {
            /* Build the address line. */
            $addr_count = count($addr_array);
            $ret = '<span class="nowrap">' . implode(',</span> <span class="nowrap">', $addr_array) . '</span>';
            if ($addr_count > 15) {
                Horde::addScriptFile('prototype.js', 'horde', true);

                $ret = '<span>' .
                    '<span onclick="[ this, this.next(), this.next(1) ].invoke(\'toggle\')" class="widget largeaddrlist">' . sprintf(_("[Show Addresses - %d recipients]"), $addr_count) . '</span>' .
                    '<span onclick="[ this, this.previous(), this.next() ].invoke(\'toggle\')" class="widget largeaddrlist" style="display:none">' . _("[Hide Addresses]") . '</span>' .
                    '<span style="display:none">' .
                    $ret . '</span></span>';
            }
        }

        return $ret;
    }

}
