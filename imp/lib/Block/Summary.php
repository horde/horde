<?php
/**
 * Block: show folder summary.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@curecanti.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Block_Summary extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Folder Summary");
    }

    /**
     */
    protected function _title()
    {
        return Horde::link(IMP_Auth::getInitialPage()->url) . $GLOBALS['registry']->get('name') . '</a>';
    }

    /**
     */
    protected function _params()
    {
        return array(
            'show_unread' => array(
                'type' => 'boolean',
                'name' => _("Only display folders with unread messages in them?"),
                'default' => 0
            ),
            'show_total' => array(
                'type' => 'boolean',
                'name' => _("Show total number of mails in folder?"),
                'default' => 0
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $injector, $notification, $prefs, $session;

        /* Filter on INBOX display, if requested. */
        if ($prefs->getValue('filter_on_display')) {
            $injector->getInstance('IMP_Filter')->filter('INBOX');
        }

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Get list of mailboxes to poll. */
        $poll = $injector->getInstance('IMP_Imap_Tree')->getPollList(true);
        $status = $imp_imap->statusMultiple($poll, Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_RECENT);

        $anyUnseen = false;
        $html_out = $onclick = '';
        $newmsgs = array();

        foreach ($poll as $mbox) {
            $mbox_str = strval($mbox);

            if (isset($status[$mbox_str]) &&
                (($mbox_str == 'INBOX') ||
                 ($session->get('imp', 'protocol') != 'pop')) &&
                (empty($this->_params['show_unread']) ||
                 !empty($status[$mbox_str]['unseen']))) {
                 $mbox_status = $status[$mbox_str];

                if (!empty($mbox_status['recent'])) {
                    $newmsgs[$mbox_str] = $mbox_status['recent'];
                }

                $html_out .= '<tr style="cursor:pointer" class="text"' . $onclick . '><td>';

                if (!empty($mbox_status['unseen'])) {
                    $html_out .= '<strong>';
                    $anyUnseen = true;
                }

                $html_out .= IMP::generateIMPUrl('mailbox.php', $mbox_str)->link() . $mbox->display . '</a>';

                if (!empty($mbox_status['unseen'])) {
                    $html_out .= '</strong>';
                }
                $html_out .= '</td><td>' .
                    (!empty($mbox_status['unseen']) ? '<strong>' . $mbox_status['unseen'] . '</strong>' : '0') .
                    (!empty($this->_params['show_total']) ? '</td><td>(' . $mbox_status['messages'] . ')' : '') .
                    '</td></tr>';
            }
        }

        if (!empty($newmsgs)) {
            /* Open the mailbox R/W to ensure the 'recent' flags are cleared
             * from the current mailbox. */
            foreach ($newmsgs as $mbox => $nm) {
                $imp_imap->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);
            }
        } elseif (!empty($this->_params['show_unread'])) {
            if (count($folders) == 0) {
                $html_out = _("No folders are being checked for new mail.");
            } elseif (!$anyUnseen) {
                $html_out = '<em>' . _("No folders with unseen messages") . '</em>';
            } elseif ($prefs->getValue('nav_popup')) {
                $html_out = '<em>' . _("No folders with new messages") . '</em>';
            }
        }

        $html = '<table cellspacing="0" width="100%">';

        /* Quota info, if available. */
        Horde::startBuffer();
        IMP::quota();
        $quota_msg = Horde::endBuffer();
        if (!empty($quota_msg)) {
            $html .= '<tr><td colspan="3">' . $quota_msg . '</td></tr>';
        }

        /* Check to see if user wants new mail notification, but only
         * if the user is logged into IMP. */
        if ($prefs->getValue('nav_popup')) {
            /* Always include these scripts so they'll be there if there's
             * new mail in later dynamic updates. */
            Horde::addScriptFile('effects.js', 'horde');
            Horde::addScriptFile('redbox.js', 'horde');
        }

        if (!empty($newmsgs) &&
            ($prefs->getValue('nav_audio') ||
             $prefs->getValue('nav_popup'))) {
            Horde::startBuffer();
            IMP::newmailAlerts($newmsgs);
            $notification->notify(array('listeners' => 'audio'));
            $html .= Horde::endBuffer();
        }

        return $html . $html_out . '</table>';
    }

}
