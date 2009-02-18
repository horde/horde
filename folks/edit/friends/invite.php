<?php
/**
 * $Id: blacklist.php 1234 2009-01-28 18:44:02Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/../../lib/base.php';
require_once FOLKS_BASE . '/lib/base.php';
require_once FOLKS_BASE . '/edit/tabs.php';

$title = _("Invite friend");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Manage adding groups
$form = new Horde_Form($vars, $title, 'addgroup');
$translated = Horde::loadConfiguration('groups.php', 'groups', 'folks');
asort($translated);
$form->addVariable(_("Friend's e-mail"), 'email', 'email', true);
$form->addVariable(_("Subject"), 'subject', 'text', false);
$form->addVariable(_("Body"), 'subject', 'longtext', false);

if ($form->validate()) {
    $form->getInfo(null, $info);

    // Fix title
    if (empty($info['subject'])) {
        $info['subject'] = sprintf(_("%s Invited to join %s."), Auth::getAuth(), $registry->get('name', 'horde'));
    }

    // Add body
    $info['body'] = sprintf(_("%s Invited to join %s."), Auth::getAuth(), $registry->get('name', 'horde'))
                    . ' '
                    . sprintf(_("Sign up at %s"), Horde::applicationUrl('account/signup.php', true));

    $result = Folks::sendMail($info['email'], $info['subject'], $info['body']);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } else {
        $notification->push(sprintf(_("Friend \"%s\" was invited to join %s."), $info['email'], $registry->get('name', 'horde')), 'horde.success');
    }
}

Horde::addScriptFile('popup.js', 'horde', true);

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('friends');
require FOLKS_TEMPLATES . '/edit/invite.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';