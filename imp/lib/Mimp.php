<?php
/**
 * MIMP Base Class - provides minimalist view functions.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Mimp
{
    /**
     * Take a Horde_Mobile_card and add global menu items.
     *
     * @param Horde_Mobile_linkset $menu  The menu linkset, with page-specific
     *                                    options already filled in.
     * @param string $page                The current page ('compose',
     *                                    'folders', 'mailbox', 'message',
     *                                    'search').
     */
    public function addMIMPMenu($menu, $page)
    {
        $items = array();

        if (!in_array($page, array('mailbox', 'message')) ||
            ($GLOBALS['imp_mbox']['mailbox'] != 'INBOX')) {
            $items[] = array(_("Inbox"), IMP::generateIMPUrl('mailbox-mimp.php', 'INBOX'));
        }

        if (!in_array($page, array('compose', 'search')) && IMP::canCompose()) {
            $items[] = array(_("New Message"), Horde::applicationUrl('compose-mimp.php')->add('u', uniqid(mt_rand())));
        }

        if (!in_array($page, array('folders', 'search'))) {
            $items[] = array(_("Folders"), Horde::applicationUrl('folders-mimp.php'));
        }

        $items[] = array(_("Log out"), Horde::getServiceLink('logout', 'imp'));

        foreach ($items as $val) {
            $menu->add(new Horde_Mobile_link($val[0], $val[1]));
        }

        $menu = new Horde_Menu();
        foreach ($menu->getSiteLinks() as $menuitem) {
            if ($menuitem != 'separator') {
                $menu->add(new Horde_Mobile_link($menuitem['text'], $menuitem['url']));
            }
        }
    }

}
