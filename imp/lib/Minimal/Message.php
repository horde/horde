<?php
/**
 * Message page for minimal view.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Minimal_Message extends IMP_Minimal_Base
{
    /**
     * URL Parameters:
     *   a: (string) actionID
     *   allto: (boolean) View all To addresses?
     *   buid: (string) TODO
     *   mt: (string) Message token
     *   fullmsg: (boolean) View full message?
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $prefs;

        $imp_mailbox = $this->indices->mailbox->list_ob;
        $imp_mailbox->setIndex($this->indices);

        $mailbox_url = IMP_Minimal_Mailbox::url(array(
            'mailbox' => $this->indices->mailbox
        ));

        /* Make sure we have a valid index. */
        if (!$imp_mailbox->isValidIndex()) {
            $mailbox_url->add('a', 'm')->redirect();
        }

        $imp_ui = $injector->getInstance('IMP_Message_Ui');

        /* Run through action handlers */
        $msg_delete = false;
        switch ($this->vars->a) {
        // 'd' = delete message
        case 'd':
            $msg_index = $imp_mailbox->getIndex();
            try {
                $injector->getInstance('Horde_Token')->validate($this->vars->mt, 'imp.message-mimp');
                $msg_delete = (bool)$injector->getInstance('IMP_Message')->delete(
                    $this->indices,
                    array('mailboxob' => $imp_mailbox)
                );
            } catch (Horde_Token_Exception $e) {
                $notification->push($e);
            }
            break;

        // 'u' = undelete message
        case 'u':
            $msg_index = $imp_mailbox->getIndex();
            $injector->getInstance('IMP_Message')->undelete($this->indices);
            break;

        // 'rs' = report spam
        // 'ri' = report innocent
        case 'rs':
        case 'ri':
            $msg_index = $imp_mailbox->getIndex();
            $msg_delete = ($injector->getInstance('IMP_Factory_Spam')->create($this->vars->a == 'rs' ? IMP_Spam::SPAM : IMP_Spam::INNOCENT)->report($this->indices, array('mailboxob' => $imp_mailbox)) === 1);
            break;
        }

        if ($msg_delete && $imp_ui->moveAfterAction($this->indices->mailbox)) {
            $imp_mailbox->setIndex(1);
        }

        /* We may have done processing that has taken us past the end of the
         * message array, so we will return to the mailbox. */
        if (!$imp_mailbox->isValidIndex() ||
            ($msg_delete && $prefs->getValue('mailbox_return'))) {
            $mailbox_url->add('s', $msg_index)->redirect();
        }

        list($mailbox, $uid) = $this->indices->getSingle();

        /* Get envelope/flag/header information. */
        try {
            $imp_imap = $mailbox->imp_imap;

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
            $mailbox_url->add('a', 'm')->redirect();
        }

        $envelope = $fetch_ret->first()->getEnvelope();
        $flags = $flags_ret->first()->getFlags();

        /* Parse the message. */
        try {
            $imp_contents = $injector->getInstance('IMP_Factory_Contents')->create($this->indices);
            $mime_headers = $imp_contents->getHeaderAndMarkAsSeen();
        } catch (IMP_Exception $e) {
            $mailbox_url->add('a', 'm')->redirect();
        }

        /* Get the starting index for the current message and the message
         * count. */
        $msgindex = $imp_mailbox->getIndex();
        $msgcount = count($imp_mailbox);

        /* Generate the mailbox link. */
        $mailbox_link = $mailbox_url->add('s', $msgindex);
        $self_link = self::url(array(
            'buid' => $this->vars->buid,
            'mailbox' => $mailbox
        ));

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
        switch($priority = $injector->getInstance('IMP_Mime_Headers')->getPriority($mime_headers)) {
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
            'buid' => $this->vars->buid,
            'identity' => $identity,
            'mailbox' => $this->indices->mailbox
        );

        $menu = array();
        if ($this->indices->mailbox->access_deletemsgs) {
            $menu[] = in_array(Horde_Imap_Client::FLAG_DELETED, $flags)
                ? array(_("Undelete"), $self_link->copy()->add('a', 'u'))
                : array(_("Delete"), $self_link->copy()->add(array('a' => 'd', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
        }

        /* Add compose actions (Reply, Reply List, Reply All, Forward,
         * Redirect, Edit as New). */
        if (IMP_Compose::canCompose()) {
            $clink_ob = new IMP_Compose_Link();
            $clink = $clink_ob->link()->add($compose_params);

            $menu[] = array(_("Reply"), $clink->add(array('a' => 'r')));

            if ($list_info['reply_list']) {
                $menu[] = array(_("Reply to List"), $clink->add(array('a' => 'rl')));
            }

            $addr_ob = clone($envelope->to);
            $addr_ob->add($envelope->cc);
            $addr_ob->setIteratorFilter(0, $user_identity->getAllFromAddresses());

            if (count($addr_ob)) {
                $menu[] = array(_("Reply All"), $clink->add(array('a' => 'ra')));
            }

            $menu[] = array(_("Forward"), $clink->add(array('a' => 'f')));
            $menu[] = array(_("Redirect"), $clink->add(array('a' => 'rc')));
            $menu[] = array(_("Edit as New"), $clink->add(array('a' => 'en')));
        }

        /* Generate previous/next links. */
        if ($prev_msg = $imp_mailbox[$imp_mailbox->getIndex() - 1]) {
            $menu[] = array(_("Previous Message"), self::url(array(
                'buid' => $imp_mailbox->getBuid($prev_msg['m'], $prev_msg['u']),
                'mailbox' => $this->indices->mailbox
            )));
        }
        if ($next_msg = $imp_mailbox[$imp_mailbox->getIndex() + 1]) {
            $menu[] = array(_("Next Message"), self::url(array(
                'buid' => $imp_mailbox->getBuid($next_msg['m'], $next_msg['u']),
                'mailbox' => $this->indices->mailbox
            )));
        }

        $menu[] = array(sprintf(_("To %s"), $this->indices->mailbox->label), $mailbox_link);

        if ($mailbox->spam_show) {
            $menu[] = array(_("Report as Spam"), $self_link->copy()->add(array('a' => 'rs', 'mt' => $injector->getInstance('Horde_Token')->get('imp.message-mimp'))));
        }

        if ($mailbox->innocent_show) {
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
                $val = Horde_String::substr($val, 0, $pos);
                $tmp['all_to'] = $self_link->copy()->add('allto', 1);
            }
            $tmp['val'] = $val;
            $hdrs[] = $tmp;
        }
        $this->view->hdrs = $hdrs;

        $atc = array();
        foreach ($inlineout['atc_parts'] as $key) {
            $summary = $imp_contents->getSummary($key, IMP_Contents::SUMMARY_BYTES | IMP_Contents::SUMMARY_SIZE | IMP_Contents::SUMMARY_DESCRIP | IMP_Contents::SUMMARY_DOWNLOAD);

            $tmp = array(
                'descrip' => $summary['description_raw'],
                'size' => $summary['size'],
                'type' => $summary['type']
            );

            if (!empty($summary['download'])) {
                /* Preference: if set, only show download confirmation screen
                 * if attachment over a certain size. */
                $tmp['download'] = ($summary['bytes'] > $prefs->getValue('mimp_download_confirm'))
                    ? IMP_Minimal_Messagepart::url(array(
                          'buid' => $this->vars->buid,
                          'mailbox' => $this->indices->mailbox
                      ))->add('atc', $key)
                    : $summary['download_url'];
            }

            if ($imp_contents->canDisplay($key, IMP_Contents::RENDER_INLINE)) {
                $tmp['view'] = IMP_Minimal_Messagepart::url(array(
                    'buid' => $this->vars->buid,
                    'mailbox' => $this->indices->mailbox
                ))->add('id', $key);
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
     *   - buid: (string) BUID of message.
     *   - mailbox: (string) Mailbox of message.
     */
    static public function url(array $opts = array())
    {
        return IMP_Mailbox::get($opts['mailbox'])->url('minimal', $opts['buid'])->add('page', 'message');
    }

}
