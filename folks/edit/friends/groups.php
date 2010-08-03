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

$title = _("Groups");

// Load driver
require_once FOLKS_BASE . '/lib/Friends.php';
$friends = Folks_Friends::singleton();

// Get groups
$groups = $friends->getGroups();
if ($groups instanceof PEAR_Error) {
    $notification->push($groups);
    $groups = array();
}

// Handle action
$action = Horde_Util::getFormData('action');
switch ($action) {
case 'delete':

    $g = Horde_Util::getFormdata('g');
    $result = $friends->removeGroup($g);
    if ($result instanceof PEAR_Error) {
        $notification->push($result);
    } elseif ($result) {
        $notification->push(sprintf(_("Group \"%s\" has been deleted."), $groups[$g]), 'horde.success');
    }

    Horde::applicationUrl('edit/groups.php')->redirect();

break;

case 'edit':

    $g = Horde_Util::getFormdata('g');
    $form = new Horde_Form($vars, _("Rename group"), 'editgroup');
    $form->addHidden('action', 'action', 'text', 'edit');
    $form->addHidden('g', 'g', 'text', 'edit');
    $form->setButtons(array(_("Rename"), _("Cancel")), _("Reset"));
    $v = $form->addVariable(_("Old name"), 'old_name', 'text', false, true);
    $v->setDefault($groups[$g]);
    $v = $form->addVariable(_("New name"), 'new_name', 'text', true);
    $v->setDefault($groups[$g]);

    if (Horde_Util::getFormData('submitbutton') == _("Cancel")) {
        $notification->push(sprintf(_("Group \"%s\" has not been renamed."), $groups[$g]), 'horde.warning');
        Horde::applicationUrl('edit/groups.php')->redirect();
    } elseif (Horde_Util::getFormData('submitbutton') == _("Rename")) {
        $new_name = Horde_Util::getFormData('new_name');
        $result = $friends->renameGroup($g, $new_name);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            $notification->push(sprintf(_("Group \"%s\" has been renamed to \"%s\"."), $groups[$g], $new_name), 'horde.success');
            Horde::applicationUrl('edit/groups.php')->redirect();
        }
    }

break;

default:

    // Manage adding groups
    $form = new Horde_Form($vars, _("Add group"), 'addgroup');
    $translated = Horde::loadConfiguration('groups.php', 'groups', 'folks');
    asort($translated);
    $form->addHidden('action', 'action', 'text', 'add');
    $form->addVariable(_("Name"), 'translated_name', 'radio', false, false, null, array($translated, true));
    $form->addVariable(_("Name"), 'custom_name', 'text', false, false, _("Enter custom name"));

    if ($form->validate()) {
        $form->getInfo(null, $info);
        if (empty($info['custom_name'])) {
            $name = $info['translated_name'];
        } else {
            $name = $info['custom_name'];
        }
        $result = $friends->addGroup($name);
        if ($result instanceof PEAR_Error) {
            $notification->push($result);
        } else {
            if (empty($info['custom_name'])) {
                $name = $translated[$info['translated_name']];
            }
            $notification->push(sprintf(_("Group \"%s\" was success added."), $name), 'horde.success');
            Horde::applicationUrl('edit/groups.php')->redirect();
        }
    }

break;
}

$remove_url = Horde_Util::addParameter(Horde::applicationUrl('edit/friends/groups.php'), 'action', 'delete');
$remove_img = Horde::img('delete.png');
$edit_url = Horde_Util::addParameter(Horde::applicationUrl('edit/friends/groups.php'), 'action', 'edit');
$edit_img = Horde::img('edit.png');
$perms_url = Horde::applicationUrl('perms.php');
$perms_img = Horde::img('perms.png');
$members_url = Horde::applicationUrl('edit/friends/friends.php');
$members_img = Horde::img('group.png');

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';

echo $tabs->render('groups');
require FOLKS_TEMPLATES . '/edit/groups.php';

require $registry->get('templates', 'horde') . '/common-footer.inc';
