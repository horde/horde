<?php
/**
 * Add new mail notifications to the stack.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Notification_Handler_Decorator_NewmailNotify
extends Horde_Core_Notification_Handler_Decorator_Base
{
    /**
     */
    protected $_app = 'imp';

    /**
     * Listeners are handling their messages.
     *
     * @param Horde_Notification_Handler  $handler   The base handler object.
     * @param Horde_Notification_Listener $listener  The Listener object that
     *                                               is handling its messages.
     */
    public function notify(Horde_Notification_Handler $handler,
                           Horde_Notification_Listener $listener)
    {
        global $injector, $prefs, $session;

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        if (!$prefs->getValue('newmail_notify') ||
            !($listener instanceof Horde_Notification_Listener_Status) ||
            !$imp_imap->imap ||
            !$imp_imap->ob) {
            return;
        }

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $ns = $imp_imap->getNamespace();
        $recent = array();

        foreach ($imp_imap->statusMultiple($injector->getInstance('IMP_Imap_Tree')->getPollList(), Horde_Imap_Client::STATUS_RECENT, array('sort' => true, 'sort_delimiter' => $ns['delimiter'])) as $key => $val) {
            if (!empty($val['recent'])) {
                /* Open the mailbox R/W so we ensure the 'recent' flag is
                 * cleared. */
                $imp_imap->openMailbox($key, Horde_Imap_Client::OPEN_READWRITE);

                $mbox = IMP_Mailbox::get($key);
                $recent[$mbox->display] = $val['recent'];
                $ajax_queue->poll($mbox);
            }
        }

        /* Don't show newmail notification on initial login. */
        if (empty($recent) ||
            !$session->get('imp', 'newmail_init')) {
            $session->set('imp', 'newmail_init', true);
            return;
        }

        $recent_sum = array_sum($recent);
        reset($recent);

        switch (count($recent)) {
        case 1:
            $mbox_list = key($recent);
            break;

        case 2:
            $mbox_list = implode(_(" and "), array_keys($recent));
            break;

        default:
            $akeys = array_keys($recent);
            $mbox_list = $akeys[0] . ', ' . $akeys[1] . ', ' . _("and") . ' ' . $akeys[2];
            if ($addl_mbox = count($recent) - 3) {
                $mbox_list .= ' (' . sprintf(ngettext("and %d more mailbox", "and %d more mailboxes", $addl_mbox), $addl_mbox) . ')';
            }
            break;
        }

        $handler->push(sprintf(ngettext("You have %d new mail message in %s.", "You have %d new mail messages in %s.", $recent_sum), $recent_sum, $mbox_list), 'horde.message');

        if ($audio = $prefs->getValue('newmail_audio')) {
            $handler->attach('audio');
            $handler->push(Horde_Themes::sound($audio), 'audio');
        }
    }

}
