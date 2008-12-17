<?php
/**
 * The IMP_UI_Mailbox:: class is designed to provide a place to store common
 * code shared among IMP's various UI views for the mailbox page.
 *
 * Copyright 2006-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_UI_Mailbox
{
    /**
     * The current mailbox.
     *
     * @var string
     */
    private $_mailbox;

    /**
     * Cached data.
     *
     * @var array
     */
    private $_cache = array();

    /**
     * Constructor.
     *
     * @param string $mailbox  The current mailbox.
     */
    function __construct($mailbox = null)
    {
        $this->_mailbox = $mailbox;
    }

    /**
     * Get From address information for display on mailbox page.
     *
     * @param array $ob       An array of envelope information.
     * @param array $options  Additional options:
     * <pre>
     * 'fullfrom' - (boolean) If true, returns 'fullfrom' information.
     *              DEFAULT: false
     * 'specialchars' - (string) If set, run 'from' return through
     *                  htmlspecialchars() using the given charset.
     * </pre>
     *
     * @return array  An array of information:
     * <pre>
     * 'error' - (boolean)
     * 'from' - (string)
     * 'fullfrom' - (string)
     * 'to' - (boolean)
     * </pre>
     */
    public function getFrom($ob, $options = array())
    {
        $ret = array('error' => false, 'to' => false);

        if (empty($ob['from'])) {
            $ret['from'] = $ret['fullfrom'] = _("Invalid Address");
            $ret['error'] = true;
            return $ret;
        }

        if (!isset($this->_cache['drafts_sm_folder'])) {
            $this->_cache['drafts_sm_folder'] = IMP::isSpecialFolder($this->_mailbox);
        }

        $from = Horde_Mime_Address::getAddressesFromObject($ob['from']);
        $from = reset($from);

        if (empty($from)) {
            $ret['from'] = _("Invalid Address");
            $ret['error'] = true;
        } else {
            $identity = &Identity::singleton(array('imp', 'imp'));
            if ($identity->hasAddress($from['inner'])) {
                /* This message was sent by one of the user's identity
                 * addresses - show To: information instead. */
                if (empty($ob['to'])) {
                    $ret['from'] = _("Undisclosed Recipients");
                    $ret['error'] = true;
                } else {
                    $to = Horde_Mime_Address::getAddressesFromObject($ob['to']);
                    $first_to = reset($to);
                    if (empty($first_to)) {
                        $ret['from'] = _("Undisclosed Recipients");
                        $ret['error'] = true;
                    } else {
                        $ret['from'] = empty($first_to['personal'])
                            ? $first_to['inner']
                            : $first_to['personal'];
                        if (!empty($options['fullfrom'])) {
                            $ret['fullfrom'] = $first_to['display'];
                        }
                    }
                }
                if (!$this->_cache['drafts_sm_folder']) {
                    $ret['from'] = _("To") . ': ' . $ret['from'];
                }
                $ret['to'] = true;
            } else {
                $ret['from'] = empty($from['personal'])
                    ? $from['inner']
                    : $from['personal'];
                if ($this->_cache['drafts_sm_folder']) {
                    $ret['from'] = _("From") . ': ' . $ret['from'];
                }
                if (!empty($options['fullfrom'])) {
                    $ret['fullfrom'] = $from['display'];
                }
            }
        }

        if (!empty($options['fullfrom']) && !isset($ret['fullfrom'])) {
            $ret['fullfrom'] = $ret['from'];
        }

        if (!empty($ret['from']) && !empty($options['specialchars'])) {
            $old_error = error_reporting(0);
            $res = htmlspecialchars($ret['from'], ENT_QUOTES, $options['specialchars']);
            if (empty($res)) {
                $res = htmlspecialchars($ret['from']);
            }
            $ret['from'] = $res;
            error_reporting($old_error);
        }

        return $ret;
    }

    /**
     * Get size display information.
     *
     * @param integer $size  The size of the message, in bytes.
     *
     * @return string  A formatted size string.
     */
    public function getSize($size)
    {
        if ($size < 1024) {
            return $size;
        }

        if (!isset($this->_cache['localeinfo'])) {
            $this->_cache['localeinfo'] = NLS::getLocaleInfo();
        }

        $size = $size / 1024;

        return ($size > 1024)
            ? sprintf(_("%s MB"), number_format($size / 1024, 1, $this->_cache['localeinfo']['decimal_point'], $this->_cache['localeinfo']['thousands_sep']))
            : sprintf(_("%s KB"), number_format($size, 0, $this->_cache['localeinfo']['decimal_point'], $this->_cache['localeinfo']['thousands_sep']));
    }

    /**
     * The list of ALT text to use for mailbox display icons.
     *
     * @return array  Type -> ALT text mappings.
     */
    public function getAttachmentAltList()
    {
        return array(
            'signed' => _("Message is signed"),
            'encrypted' => _("Message is encrypted"),
            'attachment' => _("Message has attachments")
        );
    }

    /**
     * Return the icon to use for a given attachment.
     *
     * @return string  The mailbox display icon type.
     */
    public function getAttachmentType($type)
    {
        list($primary, $sub) = explode('/', $type, 2);
        if ($primary == 'multipart') {
            switch ($sub) {
            case 'signed':
                return 'signed';

            case 'encrypted':
                return 'encrypted';

            case 'alternative':
            case 'related':
                /* Treat this as no attachments. */
                break;

            default:
                return 'attachment';
            }
        } elseif ($type == 'application/pkcs7-mime') {
             return 'encrypted';
        }

        return '';
    }

    /**
     * Formats the date header.
     *
     * @param integer $date  The UNIX timestamp.
     *
     * @return string  The formatted date header.
     */
    public function getDate($date)
    {
        if (!isset($this->_cache['today_start'])) {
            $this->_cache['today_start'] = strtotime('today');
            $this->_cache['today_end'] = strtotime('today + 1 day');
        }

        try {
            $d = new DateTime($date);
        } catch (Exception $e) {
            /* Bug #5717 - Check for UT vs. UTC. */
            if (substr(rtrim($date), -3) != ' UT') {
                return _("Unknown Date");
            }
            try {
                $d = new DateTime($date . 'C');
            } catch (Exception $e) {
                return _("Unknown Date");
            }
        }
        $udate = $d->format('U');

        if (($udate < $this->_cache['today_start']) ||
            ($udate > $this->_cache['today_end'])) {
            /* Not today, use the date. */
            return strftime($GLOBALS['prefs']->getValue('date_format'), $udate);
        }

        /* Else, it's today, use the time. */
        return strftime($GLOBALS['prefs']->getValue('time_format'), $udate);
    }

    /**
     * Formats the subject header.
     *
     * @param string $subject     The MIME encoded subject header.
     * @param string $htmlspaces  Run through Text::htmlSpaces()?
     *
     * @return string  The formatted subject header.
     */
    public function getSubject($subject, $htmlspaces = false)
    {
        $subject = Horde_Mime::decode($subject);
        if (empty($subject)) {
            return _("[No Subject]");
        }

        $new_subject = $subject = IMP::filterText(preg_replace("/\s+/", ' ', $subject));

        if ($htmlspaces) {
            require_once 'Horde/Text.php';
            $new_subject = Text::htmlSpaces($subject);
            if (empty($new_subject)) {
                $new_subject = htmlspecialchars($subject);
            }
        }

        return empty($new_subject) ? $subject : $new_subject;
    }

}
