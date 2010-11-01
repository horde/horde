<?php
/**
 * Provides mobile view (MIMP) helper functions.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Ui_Mimp
{
    /**
     * Output the menu.
     *
     * @param string $page  The current page ('compose', 'folders', 'mailbox',
     *                                        'message', 'search').
     * @param array $items  TODO
     *
     * @return string  The menu.
     */
    public function getMenu($page, $items = array())
    {
        if (!in_array($page, array('mailbox', 'message')) ||
            (IMP::$mailbox != 'INBOX')) {
            $items[] = array(_("Inbox"), IMP::generateIMPUrl('mailbox-mimp.php', 'INBOX'));
        }

        if (!in_array($page, array('compose', 'search')) && IMP::canCompose()) {
            $items[] = array(_("New Message"), Horde::url('compose-mimp.php')->unique());
        }

        if (!in_array($page, array('folders', 'search'))) {
            $items[] = array(_("Folders"), Horde::url('folders-mimp.php'));
        }

        $items[] = array(_("Log out"), Horde::getServiceLink('logout', 'imp'));

        $menu = new Horde_Menu();
        foreach ($menu->getSiteLinks() as $menuitem) {
            if ($menuitem != 'separator') {
                $items[] = array($menuitem['text'], $menuitem['url']);
            }
        }

        $out = '<ol>';
        foreach ($items as $val) {
            $out .= '<li><a href="' . $val[1] . '">' . htmlspecialchars($val[0]) . '</a></li>';
        }
        return $out . '</ol>';
    }

}
