<?php
/**
 * Re
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Horde_Block
 * @author  Michael Slusarz <slusarz@curecanti.org>
 */
class Horde_Block_imp_newmail extends Horde_Block
{
    var $_app = 'imp';

    function _content()
    {
        $GLOBALS['authentication'] = 'none';
        require_once dirname(__FILE__) . '/../base.php';

        if (!IMP::checkAuthentication(true)) {
            return '';
        }

        /* Filter on INBOX display, if requested. */
        if ($GLOBALS['prefs']->getValue('filter_on_display')) {
            IMP_Filter::filter('INBOX');
        }

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\seen', false);
        $ids = $GLOBALS['imp_search']->runSearchQuery($query, 'INBOX', Horde_Imap_Client::SORT_ARRIVAL, 1);

        $html = '<table cellspacing="0" width="100%">';
        if (empty($ids)) {
            $html .= '<tr><td><em>' . _("No unread messages") . '</em></td></tr>';
        } else {
            require_once 'Horde/Identity.php';

            $charset = NLS::getCharset();
            $imp_ui = new IMP_UI_Mailbox('INBOX');
            $shown = empty($this->_params['msgs_shown']) ? 3 : $this->_params['msgs_shown'];

            try {
                $fetch_ret = $GLOBALS['imp_imap']->ob->fetch('INBOX', array(
                    Horde_Imap_Client::FETCH_ENVELOPE => true
                ), array('ids' => array_slice($ids, 0, $shown)));
                reset($fetch_ret);
            } catch (Horde_Imap_Client_Exception $e) {
                $fetch_ret = array();
            }

            while (list($uid, $ob) = each($fetch_ret)) {
                $date = $imp_ui->getDate($ob['envelope']['date']);
                $from = $imp_ui->getFrom($ob, array('specialchars' => $charset));
                $subject = $imp_ui->getSubject($ob['envelope']['subject'], true);

                $html .= '<tr style="cursor:pointer" class="text" onclick="DimpBase.go(\'msg:INBOX:' . $uid . '\');return false;"><td>' .
                    '<strong>' . $from['from'] . '</strong><br />' .
                    $subject . '</td>' .
                    '<td>' . htmlspecialchars($date, ENT_QUOTES, $charset) . '</td></tr>';
            }

            $more_msgs = count($ids) - $shown;
            $text = ($more_msgs > 0)
                ? sprintf(ngettext("%d more unseen message...", "%d more unseen messages...", $more_msgs), $more_msgs)
                : _("Go to your Inbox...");
            $html .= '<tr><td colspan="2" style="cursor:pointer" align="right" onclick="DimpBase.go(\'folder:INBOX\');return false;">' . $text . '</td></tr>';
        }

        return $html . '</table>';
    }

}
