<?php
/**
 * Provides mobile view (MIMP) helper functions.
 *
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ui_Mimp
{
    /**
     * Output the menu.
     *
     * @param string $page  The current page ('compose', 'folders', 'mailbox',
     *                                        'message', 'search').
     * @param array $items  Additional menu items to add to the menu. First
     *                      element is label, second is URL to link to.
     *
     * @return string  The menu.
     */
    public function getMenu($page, $items = array())
    {
        if (!in_array($page, array('mailbox', 'message')) ||
            (IMP::$mailbox != 'INBOX')) {
            $items[] = array(_("Inbox"), IMP_Mailbox::get('INBOX')->url('mailbox-mimp.php'));
        }

        if (!in_array($page, array('compose', 'search')) && IMP::canCompose()) {
            $items[] = array(_("New Message"), Horde::url('compose-mimp.php')->unique());
        }

        if (!in_array($page, array('folders', 'search')) &&
            $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            $items[] = array(_("Folders"), Horde::url('folders-mimp.php'));
        }

        $items[] = array(_("Log out"), Horde::getServiceLink('logout', 'imp'));

        $menu = new Horde_Menu();
        foreach ($menu->getSiteLinks() as $menuitem) {
            if ($menuitem != 'separator') {
                $items[] = array($menuitem['text'], $menuitem['url']);
            }
        }

        $out = '<ul>';
        foreach ($items as $val) {
            $out .= '<li><a href="' . $val[1] . '">' . htmlspecialchars($val[0]) . '</a></li>';
        }
        return $out . '</ul>';
    }

}
