<?php
/**
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Horde_Block
 * @author  Michael Slusarz <slusarz@curecanti.org>
 */
class Horde_Block_dimp_newmail extends Horde_Block
{
    var $_app = 'imp';

    function _content()
    {
        $GLOBALS['authentication'] = 'none';
        require_once $GLOBALS['registry']->get('fileroot', 'imp') . '/lib/base.php';

        if (!IMP::checkAuthentication(true)) {
            return '';
        }

        /* Filter on INBOX display, if requested. */
        if ($GLOBALS['prefs']->getValue('filter_on_display')) {
            IMP_Filter::filter('INBOX');
        }

        // @todo
        $query = new IMAP_Search_Query();
        $query->seen(false);
        $ids = $GLOBALS['imp_search']->runSearchQuery($query, IMP::serverString('INBOX'), SORTARRIVAL, 1);

        $html = '<table cellspacing="0" width="100%">';
        if (empty($ids)) {
            $html .= '<tr><td><em>' . _("No unread messages") . '</em></td></tr>';
        } else {
            require_once 'Horde/Identity.php';

            $charset = NLS::getCharset();
            $identity = &Identity::singleton(array('imp', 'imp'));
            $imp_ui = new IMP_UI_Mailbox('INBOX', $charset, $identity);
            $shown = empty($this->_params['msgs_shown']) ? 3 : $this->_params['msgs_shown'];

            // @todo
            $msg_cache = &IMP_MessageCache::singleton();
            $overview = $msg_cache->retrieve('INBOX', array_slice($ids, 0, $shown), 1 | 128);
            foreach ($overview as $ob) {
                $date = $imp_ui->getDate((isset($ob->date)) ? $ob->date : null);
                $from_res = $imp_ui->getFrom($ob);
                $subject = (empty($ob->subject)) ? _("[No Subject]") : $imp_ui->getSubject($ob->subject);

                $html .= '<tr style="cursor:pointer" class="text" onclick="DimpBase.go(\'msg:INBOX:' . $ob->uid . '\');return false;"><td>' .
                    '<strong>' . htmlspecialchars($from_res['from'], ENT_QUOTES, $charset) . '</strong><br />' .
                    $subject . '</td>' .
                    '<td>' . htmlspecialchars($date, ENT_QUOTES, $charset) . '</td></tr>';
            }

            $more_msgs = count($ids) - $shown;
            if ($more_msgs) {
                $text = sprintf(ngettext("%d more unseen message...", "%d more unseen messages...", $more_msgs), $more_msgs);
            } else {
                $text = _("Go to your Inbox...");
            }
            $html .= '<tr><td colspan="2" style="cursor:pointer" align="right" onclick="DimpBase.go(\'folder:INBOX\');return false;">' . $text . '</td></tr>';
        }

        return $html . '</table>';
    }

}
