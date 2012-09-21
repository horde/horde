<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('horde', array(
    'permission' => array('horde:administration:groups')
));

$groups = $injector->getInstance('Horde_Group');
$auth = $injector->getInstance('Horde_Core_Factory_Auth')->create();

$form = $groups->readOnly() ? null : 'add.inc';
$actionID = Horde_Util::getFormData('actionID');
$gid = Horde_Util::getFormData('gid');

switch ($actionID) {
case 'addform':
    $name = Horde_Util::getFormData('name');
    try {
        $gid = $groups->create($name);
        $group = $groups->getData($gid);
        $form = 'edit.inc';
        $notification->push(sprintf(_("\"%s\" was added to the groups system."), $name), 'horde.success');
    } catch (Horde_Group_Exception $e) {
        Horde::logMessage($e, 'ERR');
        $notification->push(sprintf(_("Group was not created: %s."), $e->getMessage()), 'horde.error');
        break;
    }
    break;

case 'delete':
    if ($groups->readOnly()) {
        break;
    }
    try {
        $group = $groups->getName($gid);
        $form = 'delete.inc';
    } catch (Horde_Group_Exception $e) {
    }
    break;

case 'deleteform':
    if ($groups->readOnly() ||
        Horde_Util::getFormData('confirm') != _("Delete")) {
        break;
    }
    if (!$groups->exists($gid)) {
        $notification->push(_("Attempt to delete a non-existent group."), 'horde.error');
        break;
    }

    $name = $groups->getName($gid);
    try {
        $groups->remove($gid);
        $notification->push(sprintf(_("Successfully deleted \"%s\"."), $name), 'horde.success');
        $gid = null;
    } catch (Horde_Group_Exception $e) {
        $notification->push(sprintf(_("Unable to delete \"%s\": %s."), $name, $e->getMessage()), 'horde.error');
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
    if ($groups->readOnly()) {
        break;
    }
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
    $page_output->addInlineScript(array(
        '$("child").focus()'
    ), true);
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

$page_output->header(array(
    'title' => _("Group Administration")
));
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
$edit = $group_url->copy()->add('actionID', 'edit');
if (!$groups->readOnly()) {
    $add = $group_url->copy()->add('actionID', 'addchild');
    $add_img = Horde::img('add_group.png');
    $delete = $group_url->copy()->add('actionID', 'delete');
    $delete_img = Horde::img('delete.png', _("Delete Group"));
}

/* Set up the tree. */
$tree = $injector->getInstance('Horde_Core_Factory_Tree')->create('admin_groups', 'Javascript', array(
    'alternate' => true,
    'hideHeaders' => true
));
$tree->setHeader(array(
    array(
        'class' => 'horde-tree-spacer'
    )
));

$base_node_params = array(
    'icon' => strval(Horde_Themes::img('administration.png'))
);

foreach ($nodes as $id => $node) {
    $node_params = ($gid == $id) ? array('class' => 'selected') : array();

    $node_params['url'] = $edit->copy()->add('gid', $id);
    if ($groups->readOnly()) {
        $delete_link = null;
    } else {
        //$add_link = Horde::link($add->copy()->add('gid', $id), sprintf(_("Add a child group to \"%s\""), $name)) . $add_img . '</a>';
        $delete_link = Horde::link($delete->copy()->add('gid', $id), sprintf(_("Delete \"%s\""), $node)) . $delete_img . '</a>';
    }

    $tree->addNode(array(
        'id' => $id,
        'parent' => null,
        'label' => $node,
        'expanded' => false,
        'params' => $group_node + $node_params,
        'right' => array($spacer, $delete_link)
    ));
}

echo '<h1 class="header">' . Horde::img('group.png') . ' ' . _("Groups") . '</h1>';
$tree->renderTree();
$page_output->footer();
