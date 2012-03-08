<?php
/**
 * Block: show mailbox summary.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
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

        $this->_name = _("Mailbox Summary");
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
                'name' => _("Only display mailboxes with unread messages in them?"),
                'default' => 0
            )
        );
    }

    /**
     */
    protected function _content()
    {
        global $injector;

        /* Filter on INBOX display.  INBOX is always polled. */
        IMP_Mailbox::get('INBOX')->filterOnDisplay();

        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        /* Get list of mailboxes to poll. */
        $poll = $injector->getInstance('IMP_Imap_Tree')->getPollList(true);
        $status = $imp_imap->statusMultiple($poll, Horde_Imap_Client::STATUS_UNSEEN | Horde_Imap_Client::STATUS_MESSAGES | Horde_Imap_Client::STATUS_RECENT);

        $anyUnseen = false;
        $out = '';

        foreach ($poll as $mbox) {
            $mbox_str = strval($mbox);

            if (isset($status[$mbox_str]) &&
                ($mbox->inbox || $imp_imap->imap) &&
                (empty($this->_params['show_unread']) ||
                 !empty($status[$mbox_str]['unseen']))) {
                $mbox_status = $status[$mbox_str];

                $label = $mbox->url('mailbox.php')->link() . $mbox->display_html . '</a>';
                if (!empty($mbox_status['unseen'])) {
                    $label = '<strong>' . $label . '</strong>';
                    $anyUnseen = true;
                }
                $out .= '<tr><td>' . $label . '</td>';

                if (empty($mbox_status['unseen'])) {
                    $out .= '<td>-</td>';
                } else {
                    $out .= '<td><strong>' . intval($mbox_status['unseen']) . '</strong>';
                    if (!empty($mbox_status['recent'])) {
                        $out .= ' (<span style="color:red">' . sprintf(ngettext("%d new", "%d new", $mbox_status['recent']), $mbox_status['recent']) . '</span>)';
                    }
                    $out .='</td>';
                }

                $out .= '<td>' . intval($mbox_status['messages']) . '</td></tr>';
            }
        }

        if (!empty($this->_params['show_unread']) && !$anyUnseen) {
            return '<em>' . _("No mailboxes with unseen messages") . '</em>';
        }

        return '<table class="impBlockSummary"><thead><tr><th>' . _("Mailbox") . '</th><th>' . _("Unseen") . '</th><th>' . _("Total") . '</th></tr></thead><tbody>' .
            $out .
            '</tbody></table>';
    }

}
