<?php
/**
 * The IMP_Ui_Block:: class is designed to provide a place to store common
 * code shared among IMP's various block views.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ui_Block
{
    /**
     * Render folder summary information.
     *
     * @param string $mode  Either 'dimp' or 'imp' - defines how links are
     *                      generated.
     *
     * @return array  The HTML code and an array with mailboxes containing new
     *                messages as the keys and the number of recent messages
     *                as the values.
     */
    public function folderSummary($mode)
    {
        /* Filter on INBOX display, if requested. */
        if ($GLOBALS['prefs']->getValue('filter_on_display')) {
            $GLOBALS['injector']->getInstance('IMP_Filter')->filter('INBOX');
        }

        $imp_imap = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create();

        /* Get list of mailboxes to poll. */
        $poll = $GLOBALS['injector']->getInstance('IMP_Imap_Tree')->getPollList(true);
        $status = $imp_imap->statusMultiple($poll, Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_RECENT);

        $anyUnseen = false;
        $html = $onclick = '';
        $newmsgs = array();

        $mbox_url = ($mode == 'imp')
            ? Horde::url('mailbox.php')
            : Horde::url('#')->setAnchor('folder');

        foreach ($poll as $mbox) {
            $mbox_str = strval($mbox);

            if (isset($status[$mbox_str]) &&
                (($mbox_str == 'INBOX') ||
                 ($GLOBALS['session']->get('imp', 'protocol') != 'pop')) &&
                (empty($this->_params['show_unread']) ||
                 !empty($status[$mbox_str]['unseen']))) {
                 $mbox_status = $status[$mbox_str];

                if (!empty($mbox_status['recent'])) {
                    $newmsgs[$mbox_str] = $mbox_status['recent'];
                }

                if ($mode != 'imp') {
                    $onclick = ' onclick="try{DimpBase.go(\'mbox\', \'' . htmlspecialchars($mbox_str) . '\');}catch(e){window.location=\'' . htmlspecialchars($mbox_url . rawurlencode(':' . $mbox_str)) . '\';}return false;"';
                }

                $html .= '<tr style="cursor:pointer" class="text"' . $onclick . '><td>';

                if (!empty($mbox_status['unseen'])) {
                    $html .= '<strong>';
                    $anyUnseen = true;
                }

                $html .= ($mode == 'imp'
                          ? Horde::link($mbox_url->add('mailbox', $mbox_str))
                          : '<a>')
                    . $mbox->display . '</a>';

                if (!empty($mbox_status['unseen'])) {
                    $html .= '</strong>';
                }
                $html .= '</td><td>' .
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
                $html = _("No folders are being checked for new mail.");
            } elseif (!$anyUnseen) {
                $html = '<em>' . _("No folders with unseen messages") . '</em>';
            } elseif ($GLOBALS['prefs']->getValue('nav_popup')) {
                $html = '<em>' . _("No folders with new messages") . '</em>';
            }
        }

        return array($html, $newmsgs);
    }

}
