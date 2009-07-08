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

        if (($page != 'compose') &&
            (empty($GLOBALS['conf']['hooks']['disable_compose']) ||
             Horde::callHook('_imp_hook_disable_compose', array(true), 'imp'))) {

            $items[Horde_Util::addParameter(Horde::applicationUrl('compose-mimp.php'), 'u', uniqid(mt_rand()))] = _("New Message");
        }

        if ($page != 'folders') {
            $items[Horde::applicationUrl('folders-mimp.php')] = _("Folders");
        }

        // @TODO - Options for mobile browsers
        // if ($options_link = Horde::getServiceLink('options', 'mimp')) {
        //     $items[Horde_Util::addParameter($options_link, 'mobile', 1, false)] = _("Options");
        // }
        $logout_link = IMP::getLogoutUrl(Horde_Auth::REASON_LOGOUT);
        if (!empty($logout_link)) {
            $items[$logout_link] = _("Log out");
        }

        foreach ($items as $link => $label) {
            $menu->add(new Horde_Mobile_link($label, $link));
        }

        if (is_readable(IMP_BASE . '/config/menu.php')) {
            include IMP_BASE . '/config/menu.php';
            if (isset($_menu) && is_array($_menu)) {
                foreach ($_menu as $menuitem) {
                    if ($menuitem == 'separator') {
                        continue;
                    }
                    $menu->add(new Horde_Mobile_link($menuitem['text'], $menuitem['url']));
                }
            }
        }
    }
}
