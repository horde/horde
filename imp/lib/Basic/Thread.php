<?php
/**
 * Copyright 2004-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Message thread display.
 * Usable in both basic and dynamic views.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2004-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Basic_Thread extends IMP_Basic_Base
{
    /**
     */
    protected function _init()
    {
        global $injector, $notification, $page_output, $registry, $session;

        $imp_mailbox = $this->indices->mailbox->list_ob;

        switch ($mode = $this->vars->get('mode', 'thread')) {
        case 'thread':
            /* THREAD MODE: Make sure we have a valid index. */
            list($m, $u) = $this->indices->getSingle();
            $imp_indices = $imp_mailbox->getFullThread($u, $m);
            break;

        default:
            /* MSGVIEW MODE: Make sure we have a valid list of messages. */
            $imp_indices = $this->indices;
            break;
        }

        if (!count($imp_indices)) {
            $notification->push(_("Could not load message."), 'horde.error');
            $this->indices->mailbox->url('mailbox')->redirect();
        }

        /* Run through action handlers. */
        switch ($this->vars->actionID) {
        case 'add_address':
            try {
                $addr = new Horde_Mail_Rfc822_Address($this->vars->address);
                $addr->personal = $this->vars->name;

                $contact_link = $injector->getInstance('IMP_Contacts')->addAddress($addr);

                $notification->push(
                    sprintf(
                        _("Entry \"%s\" was successfully added to the address book"),
                        $contact_link
                    ),
                    'horde.success',
                    array('content.raw')
                );
            } catch (Horde_Exception $e) {
                $notification->push($e);
            }
            break;
        }

        $msgs = $tree = array();
        $subject = '';
        $page_label = $this->indices->mailbox->label;

        $query = new Horde_Imap_Client_Fetch_Query();
        $query->envelope();

        /* Force images to show in HTML data. */
        $injector->getInstance('IMP_Images')->alwaysShow = true;

        $multiple = (count($imp_indices) > 1);

        foreach ($imp_indices as $ob) {
            $imp_imap = $ob->mbox->imp_imap;
            $fetch_res = $imp_imap->fetch($ob->mbox, $query, array(
                'ids' => $imp_imap->getIdsOb($ob->uids)
            ));

            foreach ($ob->uids as $idx) {
                $envelope = $fetch_res[$idx]->getEnvelope();

                /* Get the body of the message. */
                $curr_msg = $curr_tree = array();
                $contents = $injector->getInstance('IMP_Factory_Contents')->create($ob->mbox->getIndicesOb($idx));
                $mime_id = $contents->findBody();
                if ($contents->canDisplay($mime_id, IMP_Contents::RENDER_INLINE)) {
                    $ret = $contents->renderMIMEPart($mime_id, IMP_Contents::RENDER_INLINE);
                    $ret = reset($ret);
                    $curr_msg['body'] = $ret['data'];

                    if (!empty($ret['js'])) {
                        $page_output->addInlineScript($ret['js'], true);
                    }
                } else {
                    $curr_msg['body'] = '<em>' . _("There is no text that can be displayed inline.") . '</em>';
                }
                $curr_msg['idx'] = $idx;

                /* Get headers for the message. */
                $date_ob = new IMP_Message_Date($envelope->date);
                $curr_msg['date'] = $date_ob->format($date_ob::DATE_LOCAL);

                if ($this->indices->mailbox->special_outgoing) {
                    $curr_msg['addr_to'] = true;
                    $curr_msg['addr'] = _("To:") . ' ' .
                        $this->_buildAddressLinks($envelope->to, Horde::selfUrlParams());
                    $addr = _("To:") . ' ' . htmlspecialchars($envelope->to->first()->label, ENT_COMPAT, 'UTF-8');
                } else {
                    $from = $envelope->from;
                    $curr_msg['addr_to'] = false;
                    $curr_msg['addr'] = $this->_buildAddressLinks($from, Horde::selfUrlParams());
                    $addr = htmlspecialchars($from->first()->label, ENT_COMPAT, 'UTF-8');
                }

                $subject_header = htmlspecialchars($envelope->subject, ENT_COMPAT, 'UTF-8');

                switch ($mode) {
                case 'thread':
                    if (empty($subject)) {
                        $subject = preg_replace('/^re:\s*/i', '', $subject_header);
                    }
                    $curr_msg['link'] = $multiple
                        ? Horde::widget(array('url' => '#display', 'title' => _("Thread List"), 'nocheck' => true))
                        : '';
                    $curr_tree['subject'] = $imp_mailbox->getThreadOb($imp_mailbox->getArrayIndex($fetch_res[$idx]->getUid(), $ob->mbox) + 1)->img;
                    break;

                default:
                    $curr_msg['link'] = Horde::widget(array('url' => '#display', 'title' => _("Back to Multiple Message View Index"), 'nocheck' => true));
                    $curr_tree['subject'] = '';
                    break;
                }

                $curr_tree['subject'] .= Horde::link('#i' . $idx) . Horde_String::truncate($subject_header, 60) . '</a> (' . $addr . ')';

                $msgs[] = $curr_msg;
                $tree[] = $curr_tree;
            }
        }

        /* Flag messages as seen. */
        $imp_indices->flag(array(Horde_Imap_Client::FLAG_SEEN));

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/thread'
        ));

        if ($mode == 'thread') {
            $view->subject = $subject;
            $view->thread = true;
        } else {
            $view->subject = sprintf(_("%d Messages"), count($msgs));
        }
        $view->messages = $msgs;
        $view->tree = $tree;

        $page_output->addScriptFile('stripe.js', 'horde');
        $page_output->addScriptFile('toggle_quotes.js', 'horde');
        $page_output->noDnsPrefetch();

        $t_css = new Horde_Themes_Element('thread.css');
        $page_output->addStylesheet($t_css->fs, $t_css->uri);
        $v_css = new Horde_Themes_Element('dynamic/message_view.css');
        $page_output->addStylesheet($v_css->fs, $v_css->uri);

        $this->output = $view->render('thread');

        $page_output->topbar = $page_output->sidebar = false;
        $this->header_params = array(
            'html_id' => 'htmlAllowScroll'
        );

        $this->title = ($mode == 'thread')
            ? _("Thread View")
            : _("Multiple Message View");
    }

    /**
     */
    public function status()
    {
        return '';
    }

    /**
     */
    public static function url(array $opts = array())
    {
        return Horde::url('basic.php')
            ->add('page', 'thread')
            ->unique()
            ->setRaw(!empty($opts['full']));
    }

    /**
     * Builds a string containing a list of addresses.
     *
     * @param Horde_Mail_Rfc822_List $addrlist  An address list.
     * @param Horde_Url $addURL                 The self URL.
     * @param boolean $link                     Link each address to the
     *                                          compose screen?
     *
     * @return string  String containing the formatted address list.
     */
    protected function _buildAddressLinks(Horde_Mail_Rfc822_List $addrlist,
                                          $addURL = null, $link = true)
    {
        global $prefs, $registry;

        $add_link = null;
        $addr_array = array();

        /* Set up the add address icon link if contact manager is
         * available. */
        if (!is_null($addURL) && $link && $prefs->getValue('add_source')) {
            try {
                $add_link = $registry->hasMethod('contacts/import')
                    ? $addURL->copy()->add('actionID', 'add_address')
                    : null;
            } catch (Horde_Exception $e) {}
        }

        $addrlist->setIteratorFilter();
        foreach ($addrlist->base_addresses as $ob) {
            if ($ob instanceof Horde_Mail_Rfc822_Group) {
                $group_array = array();
                foreach ($ob->addresses as $ad) {
                    $ret = htmlspecialchars(strval($ad));

                    if ($link) {
                        $clink = new IMP_Compose_Link(array('to' => strval($ad)));
                        $ret = Horde::link($clink->link(), sprintf(_("New Message to %s"), strval($ad))) . $ret . '</a>';
                    }

                    /* Append the add address icon to every address if contact
                     * manager is available. */
                    if ($add_link) {
                        $curr_link = $add_link->copy()->add(array(
                            'address' => $ad->bare_address,
                            'name' => $ad->personal
                        ));
                        $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ad->bare_address)) .
                            '<span class="iconImg addrbookaddImg"></span></a>';
                    }

                    $group_array[] = $ret;
                }

                $addr_array[] = htmlspecialchars($ob->groupname) . ':' .
                    (count($group_array) ? ' ' .
                    implode(', ', $group_array) : '');
            } else {
                $ret = htmlspecialchars(strval($ob));

                if ($link) {
                    $clink = new IMP_Compose_Link(array('to' => strval($ob)));
                    $ret = Horde::link($clink->link(), sprintf(_("New Message to %s"), strval($ob))) . $ret . '</a>';
                }

                /* Append the add address icon to every address if contact
                 * manager is available. */
                if ($add_link) {
                    $curr_link = $add_link->copy()->add(array(
                        'address' => $ob->bare_address,
                        'name' => $ob->personal
                    ));
                    $ret .= Horde::link($curr_link, sprintf(_("Add %s to my Address Book"), $ob->bare_address)) .
                        '<span class="iconImg addrbookaddImg"></span></a>';
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
            if ($link && $addr_count > 15) {
                $ret = '<span>' .
                    '<span onclick="[ this, this.next(), this.next(1) ].invoke(\'toggle\')" class="widget largeaddrlist">' . sprintf(_("Show Addresses (%d)"), $addr_count) . '</span>' .
                    '<span onclick="[ this, this.previous(), this.next() ].invoke(\'toggle\')" class="widget largeaddrlist" style="display:none">' . _("Hide Addresses") . '</span>' .
                    '<span style="display:none">' .
                    $ret . '</span></span>';
            }
        }

        return $ret;
    }

}
