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
class MIMP
{
    /**
     * Take a Horde_Mobile_card and add global menu items.
     *
     * @param Horde_Mobile_linkset &$menu  The menu linkset, with page-specific
     *                                     options already filled in.
     * @param string $page                 The current page ('compose',
     *                                     'folders', 'mailbox', 'message').
     */
    public function addMIMPMenu(&$menu, $page)
    {
        $items = array();

        if (!in_array($page, array('mailbox', 'message')) ||
            ($GLOBALS['imp_mbox']['mailbox'] != 'INBOX')) {
            $items[IMP::generateIMPUrl('mailbox-mimp.php', 'INBOX')] = _("Inbox");
        }

        if (($page != 'compose') && IMP::canCompose()) {
            $items[Horde_Util::addParameter(Horde::applicationUrl('compose-mimp.php'), 'u', uniqid(mt_rand()))] = _("New Message");
        }

        if ($page != 'folders') {
            $items[Horde::applicationUrl('folders-mimp.php')] = _("Folders");
        }

        // @TODO - Options for mobile browsers
        // if ($options_link = Horde::getServiceLink('options', 'mimp')) {
        //     $items[Horde_Util::addParameter($options_link, 'mobile', 1, false)] = _("Options");
        // }

        $items[Horde::getServiceLink('logout')] = _("Log out");

        foreach ($items as $link => $label) {
            $menu->add(new Horde_Mobile_link($label, $link));
        }

        foreach ($menu->getSiteLinks() as $menuitem) {
            if ($menuitem != 'separator') {
                $menu->add(new Horde_Mobile_link($menuitem['text'], $menuitem['url']));
            }
        }
    }

}
