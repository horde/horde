<?php
/**
 * Copyright 2011-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Add new mail notifications to the stack.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2011-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Notification_Handler_Decorator_NewmailNotify
extends Horde_Core_Notification_Handler_Decorator_Base
{
    /* Rate limit interval (in seconds). */
    const RATELIMIT = 30;

    /* Session variables used internally. */
    const SESS_RATELIMIT = 'newmail_ratelimit';

    /**
     */
    protected $_app = 'imp';

    /**
     */
    protected function _notify(
        Horde_Notification_Handler $handler,
        Horde_Notification_Listener $listener
    )
    {
        global $injector, $prefs, $session;

        if (!$prefs->getValue('newmail_notify') ||
            !($listener instanceof Horde_Notification_Listener_Status)) {
            return;
        }

        /* Rate limit. If rate limit is not yet set, this is the initial
         * login so skip. */
        $curr = time();
        $ratelimit = $session->get('imp', self::SESS_RATELIMIT);
        if ($ratelimit && (($ratelimit + self::RATELIMIT) > $curr)) {
            return;
        }
        $session->set('imp', self::SESS_RATELIMIT, $curr);
        if (!$ratelimit) {
            return;
        }

        $ajax_queue = $injector->getInstance('IMP_Ajax_Queue');
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();
        $recent = array();

        try {
            foreach ($imp_imap->status($injector->getInstance('IMP_Ftree')->poll->getPollList(), Horde_Imap_Client::STATUS_RECENT_TOTAL, array('sort' => true)) as $key => $val) {
                if (!empty($val['recent_total'])) {
                    /* Open the mailbox R/W so we ensure the 'recent' flag is
                     * cleared. */
                    $imp_imap->openMailbox($key, Horde_Imap_Client::OPEN_READWRITE);

                    $mbox = IMP_Mailbox::get($key);
                    $recent[$mbox->display] = $val['recent_total'];
                    $ajax_queue->poll($mbox);
                }
            }
        } catch (Exception $e) {}

        if (empty($recent)) {
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

        $text = sprintf(
            ngettext(
                "You have %d new mail message in %s.",
                "You have %d new mail messages in %s.",
                $recent_sum
            ),
            $recent_sum,
            $mbox_list
        );

        /* Status notification. */
        $handler->push($text, 'horde.message');

        /* Web notifications. */
        $handler->attach('webnotification', null, 'Horde_Core_Notification_Listener_Webnotification');
        $handler->push(
            Horde_Core_Notification_Event_Webnotification::createEvent(
                $text,
                array('icon' => strval(Horde_Themes::img('unseen.png')))
            ),
            'webnotification'
        );

        if ($audio = $prefs->getValue('newmail_audio')) {
            $handler->attach('audio');
            $handler->push(Horde_Themes::sound($audio), 'audio');
        }
    }

}
