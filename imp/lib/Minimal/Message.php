<?php
/**
 * Message page for minimal view.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl21 GPL
 * @package  IMP
 */
class IMP_Minimal_Message extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   a: (string) actionID
     *   allto: (boolean) View all To addresses?
     *   mt: (string) Message token
     *   fullmsg: (boolean) View full message?
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs;

        /* Make sure we have a valid index. */
        $imp_mailbox = IMP::mailbox()->getListOb(IMP::mailbox(true)->getIndicesOb(IMP::uid()));
        if (!$imp_mailbox->isValidIndex()) {
            IMP_Minimal_Mailbox::url()->add('a', 'm')->redirect();
        }

        $imp_hdr_ui = new IMP_Ui_Headers();
        $imp_ui = new IMP_Ui_Message();

        /* Run through action handlers */
        $msg_delete = false;
        switch ($this->vars->a) {
        // 'd' = delete message
        case 'd':
            $msg_index = $imp_mailbox->getMessageIndex();
            $imp_indices = new IMP_Indices($imp_mailbox);
            $imp_message = $injector->getInstance('IMP_Message');
            try {
                $injector->getInstance('Horde_Token')->validate($this->vars->mt, 'imp.message-mimp');
                $msg_delete = (bool)$imp_message->delete(
                    $imp_indices,
                    array('mailboxob' => $imp_mailbox)
                );
            } catch (Horde_Token_Exception $e) {
                $notification->push($e);
            }
            break;

        // 'u' = undelete message
        case 'u':
            $msg_index = $imp_mailbox->getMessageIndex();
            $imp_indices = new IMP_Indices($imp_mailbox);
            $imp_message = $injector->getInstance('IMP_Message');
            $imp_message->undelete($imp_indices);
            break;

        // 'rs' = report spam
        // 'ri' = report innocent
        case 'rs':
        case 'ri':
            $msg_index = $imp_mailbox->getMessageIndex();
            $msg_delete = (IMP_Spam::reportSpam(new IMP_Indices($imp_mailbox), $this->vars->a == 'rs' ? 'spam' : 'notspam', array('mailboxob' => $imp_mailbox)) === 1);
            break;
        }

        if ($msg_delete && $imp_ui->moveAfterAction()) {
            $imp_mailbox->setIndex(1);
        }

        /* We may have done processing that has taken us past the end of the
         * message array, so we will return to mailbox.php if that is the
         * case. */
        if (!$imp_mailbox->isValidIndex() ||
            ($msg_delete && $prefs->getValue('mailbox_return'))) {
                IMP_Minimal_Mailbox::url()->add('s', $msg_index)->redirect();
        }

        /* Now that we are done processing the messages, get the index and
         * array index of the current message. */
        $index_ob = $imp_mailbox->getIMAPIndex();
        $mailbox = $index_ob['mailbox'];
        $uid = $index_ob['uid'];

        /* Get envelope/flag/header information. */
        try {
            $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

            /* Need to fetch flags before HEADERTEXT, because SEEN flag might
             * be set before we can grab it. */
            $query = new Horde_Imap_Client_Fetch_Query();
            $query->flags();
            $flags_ret = $imp_imap->fetch($mailbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));

            $query = new Horde_Imap_Client_Fetch_Query();
            $query->envelope();
            $fetch_ret = $imp_imap->fetch($mailbox, $query, array(
                'ids' => $imp_imap->getIdsOb($uid)
            ));
        } catch (IMP_Imap_Exception $e) {
            IMP_Minimal_Mailbox::url(array('mailbox' => $mailbox))->add('a', 'm')->redirect();
        }

        $envelope = $fetch_ret->first()->getEnvelope();
        $flags = $flags_ret->first()->getFlags();

        /* Parse the message. */
        try {
            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices($imp_mailbox));
            $mime_headers = $imp_contents->getHeaderAndMarkAsSeen();
        } catch (IMP_Exception $e) {
            IMP_Minimal_Mailbox::url(array('mailbox' => $mailbox))->add('a', 'm')->redirect();
        }

        /* Get the starting index for the current message and the message
         * count. */
        $msgindex = $imp_mailbox->getMessageIndex();
        $msgcount = count($imp_mailbox);

        /* Generate the mailbox link. */
        $mailbox_link = IMP_Minimal_Mailbox::url()->add('s', $msgindex);
        $self_link = self::url(array('mailbox' => $mailbox, 'uid' => $uid));

        /* Create the Identity object. */
        $user_identity = $injector->getInstance('IMP_Identity');

        /* Develop the list of headers to display. */
        $basic_headers = $imp_ui->basicHeaders();
        $display_headers = $msgAddresses = array();

        if (($subject = $mime_headers->getValue('subject'))) {
            /* Filter the subject text, if requested. */
            $subject = Horde_String::truncate(IMP::filterText($subject), 50);
        } else {
            $subject = _("[No Subject]");
        }
        $display_headers['subject'] = $subject;

        $format_date = $imp_ui->getLocalTime($envelope->date);
        if (!empty($format_date)) {
            $display_headers['date'] = $format_date;
        }

        /* Build From address links. */
        $display_headers['from'] = $imp_ui->buildAddressLinks($envelope->from, null, false);

        /* Build To/Cc/Bcc links. */
        foreach (array('to', 'cc', 'bcc') as $val) {
            $msgAddresses[] = $mime_headers->getValue($val);
            $addr_val = $imp_ui->buildAddressLinks($envelope->$val, null, false);
            if (!empty($addr_val)) {
                $display_headers[$val] = $addr_val;
            }
        }

        /* Check for the presence of mailing list information. */
        $list_info = $imp_ui->getListInformation($mime_headers);

        /* See if the priority has been set. */
        switch($priority = $imp_hdr_ui->getPriority($mime_headers)) {
        case 'high':
        case 'low':
            $basic_headers['priority'] = _("Priority");
            $display_headers['priority'] = Horde_String::ucfirst($priority);
            break;
        }

        /* Set the status information of the message. */
        $status = '';
        $match_identity = $identity = null;

        if (!empty($msgAddresses)) {
            $match_identity = $identity = $user_identity->getMatchingIdentity($msgAddresses);
            if (is_null($identity)) {
                $identity = $user_identity->getDefault();
            }
        }

        $flag_parse = $injector->getInstance('IMP_Flags')->parse(array(
            'flags' => $flags,
            'personal' => $match_identity
        ));

        foreach ($flag_parse as $val) {
            if ($abbrev = $val->abbreviation) {
                $status .= $abbrev;
            } elseif ($val instanceof IMP_Flag_User) {
                $status .= ' *' . Horde_String::truncate($val->label, 8) . '*';
            }
        }

        /* Create the body of the message. */
        $inlineout = $imp_contents->getInlineOutput(array(
            'display_mask' => IMP_Contents::RENDER_INLINE,
            'no_inline_all' => !$prefs->getValue('mimp_inline_all')
        ));

        $msg_text = $inlineout['msgtext'];

        /* Display the first 250 characters, or display the entire message? */
        if ($prefs->getValue('mimp_preview_msg') &&
            !isset($this->vars->fullmsg) &&
            (strlen($msg_text) > 250)) {
            $msg_text = Horde_String::substr(trim($msg_text), 0, 250) . " [...]\n";
            $this->view->fullmsg_link = $self_link->copy()->add('fullmsg', 1);
        }

        $this->view->msg = nl2br($injector->getInstance('Horde_Core_Factory_TextFilter')->filter($msg_text, 'space2html'));

        $compose_params = array(
            'identity' => $identity,
            'thismailbox' => $mailbox,
            'uid' => $uid,
        );

        $menu = array();
        if (IMP::mailbox()->access_deletemsgs) {
            $menu[] = in_array(Horde_Imap_Client::FLAG_DELETED, $flags)
                ? array(_("Undelete"), $self_link->copy()->add('a', 'u'))
                : array(_("Delete"), $self_link->copy()->add(array('a' => 'd', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
        }

        /* Add compose actions (Reply, Reply List, Reply All, Forward,
         * Redirect, Edit as New). */
        if (IMP::canCompose()) {
            $menu[] = array(_("Reply"), IMP::composeLink(array(), array('a' => 'r') + $compose_params));

            if ($list_info['reply_list']) {
                $menu[] = array(_("Reply to List"), IMP::composeLink(array(), array('a' => 'rl') + $compose_params));
            }

            $addr_ob = clone($envelope->to);
            $addr_ob->add($envelope->cc);
            $addr_ob->setIteratorFilter(0, $user_identity->getAllFromAddresses());

            if (count($addr_ob)) {
                $menu[] = array(_("Reply All"), IMP::composeLink(array(), array('a' => 'ra') + $compose_params));
            }

            $menu[] = array(_("Forward"), IMP::composeLink(array(), array('a' => 'f') + $compose_params));
            $menu[] = array(_("Redirect"), IMP::composeLink(array(), array('a' => 'rc') + $compose_params));
            $menu[] = array(_("Edit as New"), IMP::composeLink(array(), array('a' => 'en') + $compose_params));
        }

        /* Generate previous/next links. */
        if ($prev_msg = $imp_mailbox->getIMAPIndex(-1)) {
            $menu[] = array(_("Previous Message"), self::url(array('mailbox' => $prev_msg['mailbox'], 'uid' => $prev_msg['uid'])));
        }
        if ($next_msg = $imp_mailbox->getIMAPIndex(1)) {
            $menu[] = array(_("Next Message"), self::url(array('mailbox' => $next_msg['mailbox'], 'uid' => $next_msg['uid'])));
        }

        $menu[] = array(sprintf(_("To %s"), IMP::mailbox()->label), $mailbox_link);

        if ($conf['spam']['reporting'] &&
            ($conf['spam']['spamfolder'] || !$mailbox->spam)) {
            $menu[] = array(_("Report as Spam"), $self_link->copy()->add(array('a' => 'rs', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
        }

        if ($conf['notspam']['reporting'] &&
            (!$conf['notspam']['spamfolder'] || $mailbox->spam)) {
            $menu[] = array(_("Report as Innocent"), $self_link->copy()->add(array('a' => 'ri', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
        }

        $this->view->menu = $this->getMenu('message', $menu);

        $hdrs = array();
        foreach ($display_headers as $head => $val) {
            $tmp = array(
                'label' => $basic_headers[$head]
            );
            if ((Horde_String::lower($head) == 'to') &&
                !isset($this->vars->allto) &&
                (($pos = strpos($val, ',')) !== false)) {
                $val = Horde_String::substr($val, 0, strpos($val, ','));
                $tmp['all_to'] = $self_link->copy()->add('allto', 1);
            }
            $tmp['val'] = $val;
            $hdrs[] = $tmp;
        }
        $this->view->hdrs = $hdrs;

        $atc = array();
        foreach ($inlineout['atc_parts'] as $key) {
            $summary = $imp_contents->getSummary($key, IMP_Contents::SUMMARY_BYTES | IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP_NOLINK_NOHTMLSPECCHARS | IMP_Contents::SUMMARY_DOWNLOAD_NOJS);

            $tmp = array(
                'descrip' => $summary['description'],
                'size' => $summary['size'],
                'type' => $summary['type']
            );

            if (!empty($summary['download'])) {
                /* Preference: if set, only show download confirmation screen
                 * if attachment over a certain size. */
                $tmp['download'] = ($summary['bytes'] > $prefs->getValue('mimp_download_confirm'))
                    ? IMP_Minimal_Messagepart::url(array('mailbox' => $mailbox, 'uid' => $uid))->add('atc', $key)
                    : $summary['download'];
            }

            if ($imp_contents->canDisplay($key, IMP_Contents::RENDER_INLINE)) {
                $tmp['view'] = IMP_Minimal_Messagepart::url(array('mailbox' => $mailbox, 'uid' => $uid))->add('id', $key);
            }

            $atc[] = $tmp;
        }
        $this->view->atc = $atc;

        $this->title = $display_headers['subject'];
        $this->view->title = ($status ? $status . ' ' : '') . sprintf(_("(Message %d of %d)"), $msgindex, $msgcount);

        $page_output->noDnsPrefetch();

        $this->_pages[] = 'message';
        $this->_pages[] = 'menu';
    }

    /**
     * @param array $opts  Options:
     *   - mailbox: (string) Mailbox of message.
     *   - uid: (string) UID of message.
     */
    static public function url(array $opts = array())
    {
        return IMP::mailbox()->url('minimal.php', $opts['uid'], $opts['mailbox'])->add('page', 'message');
    }

}
