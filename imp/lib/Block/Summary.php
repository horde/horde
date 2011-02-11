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
    public function getName()
    {
        return _("Folder Summary");
    }

    /**
     */
    protected function _title()
    {
        return Horde::link(Horde::url($GLOBALS['registry']->getInitialPage(), true)) . $GLOBALS['registry']->get('name') . '</a>';
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
        $imp_ui = new IMP_Ui_Block();
        list($html_out, $newmsgs) = $imp_ui->folderSummary($GLOBALS['session']->get('imp', 'view'));

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
        if ($GLOBALS['prefs']->getValue('nav_popup')) {
            /* Always include these scripts so they'll be there if there's
             * new mail in later dynamic updates. */
            Horde::addScriptFile('effects.js', 'horde');
            Horde::addScriptFile('redbox.js', 'horde');
        }

        if (!empty($newmsgs) &&
            ($GLOBALS['prefs']->getValue('nav_audio') ||
             $GLOBALS['prefs']->getValue('nav_popup'))) {
            Horde::startBuffer();
            IMP::newmailAlerts($newmsgs);
            $GLOBALS['notification']->notify(array('listeners' => 'audio'));
            $html .= Horde::endBuffer();
        }

        return $html . $html_out . '</table>';
    }

}
