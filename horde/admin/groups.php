<?php
/**
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$groups = $injector->getInstance('Horde_Group');
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

$form = null;
$actionID = Horde_Util::getFormData('actionID');
$gid = Horde_Util::getFormData('gid');

switch ($actionID) {
/*
case 'addchild':
    if ($gid == Horde_Group::ROOT) {
        $form = 'addchild.inc';
        $gname = _("All Groups");
    } else {
        try {
            $group = $groups->getGroupById($gid);
            $gname = $group->getShortName();
            $form = 'addchild.inc';
        } catch (Horde_Group_Exception $e) {}
    }
    break;

case 'addchildform':
    $parent = $gid;
    try {
        $child = ($parent == Horde_Group::ROOT)
            ? $groups->newGroup(Horde_Util::getFormData('child'))
            : $groups->newGroup(Horde_Util::getFormData('child'), $parent);
    } catch (Horde_Group_Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(sprintf(_("Group was not created: %s."), $e->getMessage()), 'horde.error');
        break;
    }

    try {
        $groups->addGroup($child);
        $notification->push(sprintf(_("\"%s\" was added to the groups system."), $child->getShortName()), 'horde.success');
        $group = $child;
        $form = 'edit.inc';
    } catch (Horde_Group_Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(sprintf(_("\"%s\" was not created: %s."), $child->getShortName(), $e->getMessage()), 'horde.error');
    }
    break;
*/

case 'delete':
    try {
        $group = $groups->getName($gid);
        $form = 'delete.inc';
    } catch (Horde_Group_Exception $e) {
    }
    break;

case 'deleteform':
    if (Horde_Util::getFormData('confirm') == _("Delete")) {
        if (!$groups->exists($gid)) {
            $notification->push(_("Attempt to delete a non-existent group."), 'horde.error');
            break;
        }

        $name = $groups->getName($gid);
        try {
            $groups->remove($group);
            $notification->push(sprintf(_("Successfully deleted \"%s\"."), $name), 'horde.success');
            $gid = null;
        } catch (Horde_Group_Exception $e) {
            $notification->push(sprintf(_("Unable to delete \"%s\": %s."), $name, $e->getMessage()), 'horde.error');
        }
    }
    break;

case 'edit':
    try {
        $group = $groups->getData($gid);
        $form = 'edit.inc';
        break;
    } catch (Horde_Group_Exception $e) {
    }

case 'editform':
    try {
        // Add any new users.
        $newuser = Horde_Util::getFormData('new_user');
        if (!empty($newuser)) {
            if (is_array($newuser)) {
                foreach ($newuser as $new) {
                    $groups->addUser($gid, $new);
                }
            } else {
                $groups->addUser($gid, $newuser);
            }
        }

        // Remove any users marked for purging.
        $removes = Horde_Util::getFormData('remove');
        if (!empty($removes) && is_array($removes)) {
            foreach ($removes as $user => $junk) {
                $groups->removeUser($gid, $user);
            }
        }

        // Set the email address of the group.
        $groups->setData($gid, 'email', Horde_Util::getFormData('email'));

        $notification->push(sprintf(_("Updated \"%s\"."), $groups->getName($gid)), 'horde.success');
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        // restore backup copy
        $group = $restore;
    }

    try {
        $group = $groups->getData($gid);
        $form = 'edit.inc';
    } catch (Horde_Group_Exception $e) {
    }
    break;
}

switch ($form) {
case 'addchild.inc':
    Horde::addInlineScript(array(
        '$("child").focus()'
    ), 'dom');
    break;

case 'edit.inc':
    /* Set up the lists. */
    try {
        $users = $groups->listUsers($gid);
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        $users = array();
    }

    /*
    try {
        $all_users = $group->listAllUsers();
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        $all_users = array();
    }
    $inherited_users = array_diff($all_users, $users);
    */
    $inherited_users = array();

    if ($auth->hasCapability('list')) {
        try {
            $user_list = $auth->listUsers();
        } catch (Horde_Auth_Exception $e) {
            $notification->push($e, 'horde.error');
            $user_list = array();
        }
        sort($user_list);
    } else {
        $user_list = array();
    }
    break;

}

$title = _("Group Administration");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';
if (!empty($form)) {
    require HORDE_TEMPLATES . '/admin/groups/' . $form;
}

/* Get the perms tree. */
$nodes = $groups->listAll();

/* Set up some node params. */
$spacer = '&nbsp;&nbsp;&nbsp;&nbsp;';
$group_node = array('icon' => strval(Horde_Themes::img('group.png')));
$group_url = Horde::url('admin/groups.php', true);
$add = $group_url->copy()->add('actionID', 'addchild');
$add_img = Horde::img('add_group.png');
$edit = $group_url->copy()->add('actionID', 'edit');
$delete = $group_url->copy()->add('actionID', 'delete');
$edit_img = Horde::img('edit.png', _("Edit Group"));
$delete_img = Horde::img('delete.png', _("Delete Group"));

/* Set up the tree. */
$tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('admin_groups', 'Javascript', array(
    'alternate' => true,
    'hideHeaders' => true
));
$tree->setHeader(array(
    array(
        'class' => 'treeHdrSpacer'
    )
));

$base_node_params = array(
    'icon' => strval(Horde_Themes::img('administration.png'))
);

foreach ($nodes as $id => $node) {
    $node_params = ($gid == $id) ? array('class' => 'selected') : array();

    $node_params['url'] = $edit->copy()->add('gid', $id);
    //$add_link = Horde::link($add->copy()->add('gid', $id), sprintf(_("Add a child group to \"%s\""), $name)) . $add_img . '</a>';
    $delete_link = Horde::link($delete->copy()->add('gid', $id), sprintf(_("Delete \"%s\""), $node)) . $delete_img . '</a>';

    $tree->addNode(
        $id,
        null,
        $node,
        0,
        false,
        $group_node + $node_params,
        array($spacer, $delete_link)
    );
}

echo '<h1 class="header">' . Horde::img('group.png') . ' ' . _("Groups") . '</h1>';
$tree->renderTree();
require HORDE_TEMPLATES . '/common-footer.inc';
