<?php
/**
 * Copyright 2005-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Horde_Block
 */

$block_name = _("Folder Summary");

class Horde_Block_imp_summary extends Horde_Block
{
    public $updateable = true;
    protected $_app = 'imp';

    protected function _title()
    {
        return Horde::link(Horde::url($GLOBALS['registry']->getInitialPage(), true)) . $GLOBALS['registry']->get('name') . '</a>';
    }

    protected function _params()
    {
        return array('show_unread' => array('type' => 'boolean',
                                            'name' => _("Only display folders with unread messages in them?"),
                                            'default' => 0),
                     'show_total' => array('type' => 'boolean',
                                           'name' => _("Show total number of mails in folder?"),
                                           'default' => 0)
                     );
    }

    protected function _content()
    {
        global $notification, $prefs, $registry;

        try {
            new IMP_Application(array('init' => array('authentication' => 'throw')));
        } catch (Horde_Exception $e) {
            return;
        }

        $html = '<table cellspacing="0" width="100%">';

        /* Filter on INBOX display, if requested. */
        if ($prefs->getValue('filter_on_display')) {
            $imp_filter = new IMP_Filter();
            $imp_filter->filter('INBOX');
        }

        /* Get list of mailboxes to poll. */
        $imaptree = IMP_Imap_Tree::singleton();
        $folders = $imaptree->getPollList(true);

        /* Quota info, if available. */
        $quota_msg = Horde_Util::bufferOutput(array('IMP', 'quota'));
        if (!empty($quota_msg)) {
            $html .= '<tr><td colspan="3">' . $quota_msg . '</td></tr>';
        }

        $newmsgs = array();
        $anyUnseen = false;

        foreach ($folders as $folder) {
            if (($folder == 'INBOX') ||
                ($_SESSION['imp']['protocol'] != 'pop')) {
                $info = $imaptree->getElementInfo($folder);
                if (!empty($info)) {
                    if (empty($this->_params['show_unread']) ||
                        !empty($info['unseen'])) {
                        if (!empty($info['recent'])) {
                            $newmsgs[$folder] = $info['recent'];
                        }
                        $url = Horde_Util::addParameter(Horde::applicationUrl('mailbox.php', true), array('no_newmail_popup' => 1, 'mailbox' => $folder));
                        $html .= '<tr style="cursor:pointer" class="text" onclick="self.location=\'' . $url . '\'"><td>';
                        if (!empty($info['unseen'])) {
                            $html .= '<strong>';
                            $anyUnseen = true;
                        }
                        $html .= Horde::link($url) . IMP::displayFolder($folder) . '</a>';
                        if (!empty($info['unseen'])) {
                            $html .= '</strong>';
                        }
                        $html .= '</td><td>' .
                            (!empty($info['unseen']) ? '<strong>' . $info['unseen'] . '</strong>' : '0') .
                            (!empty($this->_params['show_total']) ? '</td><td>(' . $info['messages'] . ')' : '') .
                            '</td></tr>';
                    }
                }
            }
        }

        $html .= '</table>';

        /* Check to see if user wants new mail notification, but only
         * if the user is logged into IMP. */
        if ($prefs->getValue('nav_popup')) {
            // Always include these scripts so they'll be there if
            // there's new mail in later dynamic updates.
            Horde::addScriptFile('effects.js', 'horde');
            Horde::addScriptFile('redbox.js', 'horde');
        }

        if (!empty($newmsgs)) {
            /* Open the mailbox R/W to ensure the 'recent' flags are cleared
             * from the current mailbox. */
            foreach ($newmsgs as $mbox => $nm) {
                $GLOBALS['imp_imap']->ob()->openMailbox($mbox, Horde_Imap_Client::OPEN_READWRITE);
            }

            if ($prefs->getValue('nav_popup')) {
                $html .= Horde_Util::bufferOutput(Horde::addInlineScript((IMP::getNewMessagePopup($newmsgs)), 'dom'));
            }

            if (($sound = $prefs->getValue('nav_audio'))) {
                $notification->push($registry->getImageDir() .
                                    '/audio/' . $sound, 'audio');
                $html .= Horde_Util::bufferOutput(array($notification, 'notify'), array('listeners' => 'audio'));
            }
        } elseif (!empty($this->_params['show_unread'])) {
            if (count($folders) == 0) {
                $html .= _("No folders are being checked for new mail.");
            } elseif (!$anyUnseen) {
                $html .= '<em>' . _("No folders with unseen messages") . '</em>';
            } elseif ($prefs->getValue('nav_popup')) {
                $html .= '<em>' . _("No folders with new messages") . '</em>';
            }
        }

        return $html;
    }

}
