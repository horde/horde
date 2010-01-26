<?php
/**
 * The IMP_Ui_Block:: class is designed to provide a place to store common
 * code shared among IMP's various block views.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
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
            $imp_filter = new IMP_Filter();
            $imp_filter->filter('INBOX');
        }

        /* Get list of mailboxes to poll. */
        $imptree = IMP_Imap_Tree::singleton();
        $poll = $imptree->getPollList(true);
        $status = $GLOBALS['imp_imap']->ob()->statusMultiple($poll, Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_RECENT);

        $anyUnseen = false;
        $html = '';
        $newmsgs = array();

        if ($mode == 'imp') {
            $mbox_url = Horde::applicationUrl('mailbox.php', true);
        }

        foreach ($poll as $folder) {
            if (isset($status[$folder]) &&
                (($folder == 'INBOX') ||
                 ($_SESSION['imp']['protocol'] != 'pop')) &&
                (empty($this->_params['show_unread']) ||
                 !empty($status[$folder]['unseen']))) {
                if (!empty($status[$folder]['recent'])) {
                    $newmsgs[$folder] = $status[$folder]['recent'];
                }

                if ($mode == 'imp') {
                    $onclick = 'self.location=\'' . $mbox_url->copy()->add(array('no_newmail_popup' => 1, 'mailbox' => $folder)) . '\'';
                } else {
                    $onclick = 'DimpBase.go(\'folder:' . htmlspecialchars($folder) . '\');return false;';
                }

                $html .= '<tr style="cursor:pointer" class="text" onclick="' . $onclick . '"><td>';

                if (!empty($status[$folder]['unseen'])) {
                    $html .= '<strong>';
                    $anyUnseen = true;
                }

                $html .= (($mode == 'imp') ? Horde::link($url) : '<a>') . IMP::displayFolder($folder) . '</a>';

                if (!empty($status[$folder]['unseen'])) {
                    $html .= '</strong>';
                }
                $html .= '</td><td>' .
                    (!empty($status[$folder]['unseen']) ? '<strong>' . $status[$folder]['unseen'] . '</strong>' : '0') .
                    (!empty($this->_params['show_total']) ? '</td><td>(' . $status[$folder]['messages'] . ')' : '') .
                    '</td></tr>';
            }
        }

        $html .= '</table>';

        if (!empty($newmsgs)) {
            /* Open the mailbox R/W to ensure the 'recent' flags are cleared
             * from the current mailbox. */
            foreach ($newmsgs as $mbox => $nm) {
                $GLOBALS['imp_imap']->ob()->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);
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
