<?php
/**
 * The IMP_UI_Mailbox:: class is designed to provide a place to dump common
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
class IMP_UI_Mailbox {

    /**
     */
    var $_charset;

    /**
     */
    var $_identity;

    /**
     */
    var $_mailbox;

    /**
     * Cache array.
     *
     * @var array
     */
    var $_c = array();

    /**
     */
    function IMP_UI_Mailbox($mailbox = null, $charset = null, $identity = null)
    {
        $this->_mailbox = $mailbox;
        $this->_charset = $charset;
        $this->_identity = $identity;
    }

    /**
     * TODO
     */
    function getFrom($ob, $need_full = true)
    {
        $ret = array('error' => false, 'to' => false);

        if (empty($ob['from'])) {
            $ret['from'] = $ret['fullfrom'] = _("Invalid Address");
            $ret['error'] = true;
            return $ret;
        }

        if (!isset($this->_c['drafts_sm_folder'])) {
            $this->_c['drafts_sm_folder'] = IMP::isSpecialFolder($this->_mailbox);
        }

        $from = Horde_Mime_Address::getAddressesFromObject($ob['from']);
        $from = array_shift($from);

        if (empty($from)) {
            $ret['from'] = _("Invalid Address");
            $ret['error'] = true;
        } elseif ($this->_identity &&
                  $this->_identity->hasAddress($from['inner'])) {
            /* This message was sent by one of the user's identity addresses -
             * show To information instead. */
            if (empty($ob['to'])) {
                $ret['from'] = _("Undisclosed Recipients");
                $ret['error'] = true;
            } else {
                $to = Horde_Mime_Address::getAddressesFromObject($ob['to']);
                $first_to = array_shift($to);
                if (empty($first_to)) {
                    $ret['from'] = _("Undisclosed Recipients");
                    $ret['error'] = true;
                } else {
                    $ret['from'] = empty($first_to['personal']) ? $first_to['inner'] : $first_to['personal'];
                    if ($need_full) {
                        $ret['fullfrom'] = $first_to['display'];
                    }
                }
            }
            if (!$this->_c['drafts_sm_folder']) {
                $ret['from'] = _("To") . ': ' . $ret['from'];
            }
            $ret['to'] = true;
        } else {
            $ret['from'] = empty($from['personal']) ? $from['inner'] : $from['personal'];
            if ($this->_c['drafts_sm_folder']) {
                $ret['from'] = _("From") . ': ' . $ret['from'];
            }
            if ($need_full) {
                $ret['fullfrom'] = $from['display'];
            }
        }

        if ($need_full && !isset($ret['fullfrom'])) {
            $ret['fullfrom'] = $ret['from'];
        }

        return $ret;
    }

    /**
     */
    function getSize($size)
    {
        if ($size > 1024) {
            if (!isset($this->_c['localeinfo'])) {
                $this->_c['localeinfo'] = NLS::getLocaleInfo();
            }
            $size = $size / 1024;
            if ($size > 1024) {
                return sprintf(_("%s MB"), number_format($size / 1024, 1, $this->_c['localeinfo']['decimal_point'], $this->_c['localeinfo']['thousands_sep']));
            } else {
                return sprintf(_("%s KB"), number_format($size, 0, $this->_c['localeinfo']['decimal_point'], $this->_c['localeinfo']['thousands_sep']));
            }
        }

        return $size;
    }

    /**
     */
    function getAttachmentAltList()
    {
        return array(
            'signed' => _("Message is signed"),
            'encrypted' => _("Message is encrypted"),
            'attachment' => _("Message has attachments")
        );
    }

    /**
     */
    function getAttachmentAlt($attachment)
    {
        $list = $this->getAttachmentAltList();
        return (isset($list[$attachment])) ? $list[$attachment] : $list['attachment'];
    }

    /**
     */
    function getAttachmentType($structure)
    {
        if ($structure->getPrimaryType() == 'multipart') {
            switch ($structure->getSubType()) {
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
        } elseif ($structure->getType() == 'application/pkcs7-mime') {
             return 'encrypted';
        }

        return '';
    }

    /**
     * Formats the date header.
     *
     * @param DateTime $date  A DateTime object.
     *
     * @return string  The formatted date header.
     */
    function getDate($date)
    {
        if (!isset($this->_c['today_start'])) {
            $this->_c['today_start'] = strtotime('today');
            $this->_c['today_end'] = strtotime('today + 1 day');
        }

        $d = new DateTime($date);
        $udate = $d->format('U');

        if (($udate < $this->_c['today_start']) ||
            ($udate > $this->_c['today_end'])) {
            /* Not today, use the date. */
            return strftime($GLOBALS['prefs']->getValue('date_format'), $udate);
        } else {
            /* Else, it's today, use the time. */
            return strftime($GLOBALS['prefs']->getValue('time_format'), $udate);
        }
    }

    /**
     */
    function getSubject($subject)
    {
        $subject = Horde_Mime::decode($subject, $this->_charset);
        $subject = empty($subject)
            ? _("[No Subject]")
            : IMP::filterText(preg_replace("/\s+/", ' ', $subject));
        if ($_SESSION['imp']['view'] == 'dimp') {
            require_once 'Horde/Text.php';
            $subject = str_replace('&nbsp;', '&#160;', Text::htmlSpaces($subject));
        }
        return $subject;
    }

}
