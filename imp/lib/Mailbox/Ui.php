<?php
/**
 * Copyright 2006-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Common UI code for IMP's various mailbox views.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2006-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mailbox_Ui
{
    /**
     * Cached drafts sent-mail mailbox..
     *
     * @var array
     */
    private $_draftsSent = array();

    /**
     * The current mailbox.
     *
     * @var IMP_Mailbox
     */
    private $_mailbox;

    /**
     * Constructor.
     *
     * @param IMP_Mailbox $mailbox  The current mailbox.
     */
    public function __construct($mailbox = null)
    {
        $this->_mailbox = $mailbox;
    }

    /**
     * Get From address information for display on mailbox page.
     *
     * @param Horde_Imap_Client_Data_Envelope $ob  An envelope object.
     *
     * @return array  An array of information:
     *   - from: (string) The label(s) of the From address (personal part;
     *           fallback to address).
     *   - from_addr: (string) The bare address(es) of the From address.
     *   - from_list: (Horde_Mail_Rfc822_List) From address list.
     *   - to: (boolean) True if this is who the message was sent to.
     */
    public function getFrom($ob)
    {
        $ret = array(
            'from' => '',
            'from_addr' => '',
            'to' => false
        );

        if (!isset($this->_draftsSent)) {
            $this->_draftsSent = $this->_mailbox->special_outgoing;
        }

        if ($GLOBALS['injector']->getInstance('IMP_Identity')->hasAddress($ob->from)) {
            if (!$this->_draftsSent) {
                $ret['from'] = _("To:") . ' ';
            }
            $ret['to'] = true;
            $addrs = $ob->to;

            if (!count($addrs)) {
                $ret['from'] .= _("Undisclosed Recipients");
                $ret['from_list'] = new Horde_Mail_Rfc822_List();
                return $ret;
            }
        } else {
            $addrs = $ob->from;
            if ($this->_draftsSent) {
                $ret['from'] = _("From:") . ' ';
            }

            if (!count($addrs)) {
                $ret['from'] = _("Invalid Address");
                $ret['from_list'] = new Horde_Mail_Rfc822_List();
                return $ret;
            }
        }

        $bare = $parts = array();

        $addrs->unique();
        foreach ($addrs->base_addresses as $val) {
            $bare[] = $val->bare_address;
            $parts[] = $val->label;
        }

        $ret['from'] .= implode(', ', $parts);
        $ret['from_addr'] = implode(', ', $bare);
        $ret['from_list'] = $addrs;

        return $ret;
    }

    /**
     * Formats the subject header.
     *
     * @param string $subject     The subject header.
     * @param string $htmlspaces  HTML-ize spaces?
     *
     * @return string  The formatted subject header.
     */
    public function getSubject($subject, $htmlspaces = false)
    {
        if (!strlen($subject)) {
            return _("[No Subject]");
        }

        $new_subject = $subject = IMP::filterText(preg_replace("/\s+/", ' ', $subject));

        if ($htmlspaces) {
            $new_subject = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($subject, 'space2html', array('encode' => true));
            if (empty($new_subject)) {
                $new_subject = htmlspecialchars($subject);
            }
        }

        return empty($new_subject) ? $subject : $new_subject;
    }

}
