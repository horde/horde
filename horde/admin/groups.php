<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$groups = Horde_Group::singleton();
$auth = $injector->getInstance('Horde_Auth')->getAuth();

$form = null;
$reload = false;
$actionID = Horde_Util::getFormData('actionID');
$cid = Horde_Util::getFormData('cid');

switch ($actionID) {
case 'addchild':
    if ($cid == Horde_Group::ROOT) {
        $form = 'addchild.inc';
        $gname = _("All Groups");
    } else {
        try {
            $group = $groups->getGroupById($cid);
            $gname = $group->getShortName();
            $form = 'addchild.inc';
        } catch (Horde_Group_Exception $e) {}
    }
    break;

case 'addchildform':
    $parent = $cid;
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
        $reload = true;
    } catch (Horde_Group_Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(sprintf(_("\"%s\" was not created: %s."), $child->getShortName(), $e->getMessage()), 'horde.error');
    }
    break;

case 'delete':
    try {
        $group = $groups->getGroupById($cid);
        $form = 'delete.inc';
    } catch (Horde_Group_Exception $e) {}
    break;

case 'deleteform':
    if (Horde_Util::getFormData('confirm') == _("Delete")) {
        try {
            $group = $groups->getGroupById($cid);
        } catch (Horde_Group_Exception $e) {
            $notification->push(_("Attempt to delete a non-existent group."), 'horde.error');
            break;
        }

        try {
            $groups->removeGroup($group, true);
            $notification->push(sprintf(_("Successfully deleted \"%s\"."), $group->getShortName()), 'horde.success');
            $cid = null;
            $reload = true;
        } catch (Horde_Group_Exception $e) {
            $notification->push(sprintf(_("Unable to delete \"%s\": %s."), $group->getShortName(), $e->getMessage()), 'horde.error');
        }
    }
    break;

case 'edit':
    try {
        $group = $groups->getGroupById($cid);
        $form = 'edit.inc';
        break;
    } catch (Horde_Group_Exception $e) {}

    if (($category = Horde_Util::getFormData('category')) !== null) {
        try {
            $group = $groups->getGroup($category);
            $form = 'edit.inc';
            break;
        } catch (Horde_Group_Exception $e) {}

        if (Horde_Util::getFormData('autocreate')) {
            $parent = Horde_Util::getFormData('parent');
            $group = $groups->newGroup($category);
            try {
                $groups->addGroup($group, $parent);
                $form = 'edit.inc';
            } catch (Horde_Group_Exception $e) {}
        }
    }
    break;

case 'editform':
    $group = $groups->getGroupById($cid);

    // make a copy of the group so we can restore it if there is an error.
    $restore = clone $group;

    // Add any new users.
    $newuser = Horde_Util::getFormData('new_user');
    if (!empty($newuser)) {
        if (is_array($newuser)) {
            foreach ($newuser as $new) {
                $group->addUser($new, false);
            }
        } else {
            $group->addUser($newuser, false);
        }
    }

    // Remove any users marked for purging.
    $removes = Horde_Util::getFormData('remove');
    if (!empty($removes) && is_array($removes)) {
        foreach ($removes as $user => $junk) {
            $group->removeUser($user, false);
        }
    }

    // Set the email address of the group.
    $group->set('email', Horde_Util::getFormData('email'));

    // Save the group to the backend.
    try {
        $group->save();
        $notification->push(sprintf(_("Updated \"%s\"."), $group->getShortName()), 'horde.success');
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        // restore backup copy
        $group = $restore;
    }

    $form = 'edit.inc';
    $reload = true;
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
        $users = $group->listUsers();
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        $users = array();
    }

    try {
        $all_users = $group->listAllUsers();
    } catch (Horde_Group_Exception $e) {
        $notification->push($e, 'horde.error');
        $all_users = array();
    }
    $inherited_users = array_diff($all_users, $users);

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
$nodes = $groups->listGroups(true);
$nodes[Horde_Group::ROOT] = Horde_Group::ROOT;

/* Set up some node params. */
$spacer = '&nbsp;&nbsp;&nbsp;&nbsp;';
$group_node = array('icon' => strval(Horde_Themes::img('group.png')));
$group_url = Horde::url('admin/groups.php');
$add = $group_url->copy()->add('actionID', 'addchild');
$add_img = Horde::img('add_group.png');
$edit = $group_url->copy()->add('actionID', 'edit');
$delete = $group_url->copy()->add('actionID', 'delete');
$edit_img = Horde::img('edit.png', _("Edit Group"));
$delete_img = Horde::img('delete.png', _("Delete Group"));

/* Set up the tree. */
$tree = $injector->getInstance('Horde_Tree')->getTree('admin_groups', 'Javascript', array(
    'alternate' => true,
    'hideHeaders' => true
));
$tree->setHeader(array(
    array(
        'class' => 'treeHdrSpacer'
    )
));

/* Explicitly check for > 0 since we can be called with current = -1
 * for the root node. */
if ($cid > 0) {
    $cid_parents = $groups->getGroupParentList($cid);
}

foreach ($nodes as $id => $node) {
    $node_params = ($cid == $id) ? array('class' => 'selected') : array();

    if ($id == Horde_Group::ROOT) {
        $add_link = Horde::link(Horde_Util::addParameter($add, 'cid', $id), _("Add a new group")) . $add_img . '</a>';

        $base_node_params = array('icon' => strval(Horde_Themes::img('administration.png')));
        $tree->addNode(
            $id,
            null,
            _("All Groups"),
            0,
            true,
            $base_node_params + $node_params,
            array($spacer, $add_link)
        );
    } else {
        $name = $groups->getGroupShortName($node);
        $node_params['url'] = Horde_Util::addParameter($edit, 'cid', $id);
        $add_link = Horde::link(Horde_Util::addParameter($add, 'cid', $id), sprintf(_("Add a child group to \"%s\""), $name)) . $add_img . '</a>';
        $delete_link = Horde::link(Horde_Util::addParameter($delete, 'cid', $id), sprintf(_("Delete \"%s\""), $name)) . $delete_img . '</a>';

        $tree->addNode(
            $id,
            $groups->getGroupParent($id),
            $groups->getGroupShortName($node),
            $groups->getLevel($id) + 1,
            (isset($cid_parents[$id])),
            $group_node + $node_params,
            array($spacer, $add_link, $delete_link)
        );
    }
}

echo '<h1 class="header">' . Horde::img('group.png') . ' ' . _("Groups") . '</h1>';
$tree->renderTree();
require HORDE_TEMPLATES . '/common-footer.inc';
