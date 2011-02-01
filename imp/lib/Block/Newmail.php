<?php
/**
 * Block: show list of new mail messages.
 *
 * Copyright 2007-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Block_Newmail extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->_name = _("Newest Unseen Messages");
    }

    /**
     */
    protected function _content()
    {
        /* Filter on INBOX display, if requested. */
        if ($GLOBALS['prefs']->getValue('filter_on_display')) {
            $GLOBALS['injector']->getInstance('IMP_Filter')->filter('INBOX');
        }

        $query = new Horde_Imap_Client_Search_Query();
        $query->flag('\\seen', false);
        $ids = $GLOBALS['injector']->getInstance('IMP_Search')->runQuery($query, 'INBOX', Horde_Imap_Client::SORT_SEQUENCE, 1);
        $indices = reset($ids);

        $html = '<table cellspacing="0" width="100%">';
        if (empty($indices)) {
            $html .= '<tr><td><em>' . _("No unread messages") . '</em></td></tr>';
        } else {
            $charset = 'UTF-8';
            $imp_ui = new IMP_Ui_Mailbox('INBOX');
            $shown = empty($this->_params['msgs_shown'])
                ? 3
                : $this->_params['msgs_shown'];

            try {
                $fetch_ret = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->fetch('INBOX', array(
                    Horde_Imap_Client::FETCH_ENVELOPE => true
                ), array('ids' => array_slice($indices, 0, $shown)));
                reset($fetch_ret);
            } catch (Horde_Imap_Client_Exception $e) {
                $fetch_ret = array();
            }

            while (list($uid, $ob) = each($fetch_ret)) {
                $date = $imp_ui->getDate($ob['envelope']->date);
                $from = $imp_ui->getFrom($ob['envelope'], array('specialchars' => $charset));
                $subject = $imp_ui->getSubject($ob['envelope']->subject, true);

                $html .= '<tr style="cursor:pointer" class="text" onclick="DimpBase.go(\'msg\', \'{5}INBOX' . $uid . '\');return false;"><td>' .
                    '<strong>' . $from['from'] . '</strong><br />' .
                    $subject . '</td>' .
                    '<td>' . htmlspecialchars($date, ENT_QUOTES, $charset) . '</td></tr>';
            }

            $more_msgs = count($indices) - $shown;
            $text = ($more_msgs > 0)
                ? sprintf(ngettext("%d more unseen message...", "%d more unseen messages...", $more_msgs), $more_msgs)
                : _("Go to your Inbox...");
            $html .= '<tr><td colspan="2" style="cursor:pointer" align="right" onclick="DimpBase.go();return false;">' . $text . '</td></tr>';
        }

        return $html . '</table>';
    }

}
