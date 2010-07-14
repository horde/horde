<?php
/**
 * Copyright 2000-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Martin Blumenthal <tinu@humbapa.ch>
 */

@define('SKOLI_BASE', dirname(__FILE__));
require_once SKOLI_BASE . '/lib/base.php';

// Exit if this isn't an authenticated user.
if (!$GLOBALS['registry']->getAuth()) {
    header('Location: ' . Horde::applicationUrl('list.php', true));
    exit;
}

$vars = Horde_Variables::getDefaultVariables();
$driver = &Skoli_Driver::singleton();
$entry = $driver->getEntry($vars->get('entry'));
if (is_a($entry, 'PEAR_Error') || !count($entry)) {
    $notification->push(_("Entry not found."), 'horde.error');
    header('Location: ' . Horde::applicationUrl('search.php', true));
    exit;
}
$share = $GLOBALS['skoli_shares']->getShare($entry['class_id']);

// Check permissions
if (!$share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::READ)) {
    $notification->push(_("You are not allowed to view this entry."), 'horde.error');
    header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('search.php', true), 'actionID', 'search'));
    exit;
}

$studentdetails = Skoli::getStudent($share->get('address_book'), $entry['student_id']);

// Get view.
$viewName = Horde_Util::getFormData('view', 'Entry');

if ($viewName != 'DeleteEntry') {
    require_once SKOLI_BASE . '/lib/Forms/Entry.php';
    if (!$vars->exists('class_id')) {
        foreach ($entry as $key=>$val) {
            if (!is_array($val)) {
                $vars->set($key, $val);
            }
        }
        foreach ($entry['_attributes'] as $key=>$val) {
            $vars->set('attribute_' . $key, $val);
        }
    }
    $form = new Skoli_EntryForm($vars);
    if ($viewName == 'EditEntry') {
        if ($form->validate($vars)) {
            $driver = &Skoli_Driver::singleton($vars->get('class_id'));
            $result = $driver->updateEntry($entry['object_id'], $vars);
            if (is_a($result, 'PEAR_Error')) {
                $notification->push(sprintf(_("Couldn't update this entry: %s"), $result->getMessage()), 'horde.error');
            } else {
                $notification->push(sprintf(_("The entry for \"%s\" has been saved."), $studentdetails[$conf['addresses']['name_field']]), 'horde.success');
                header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('search.php', true), 'actionID', 'search'));
                exit;
            }
        }
    }
}

// Entry actions.
$actionID = Horde_Util::getFormData('actionID');
if ($actionID == 'delete') {
    if (is_a($deleted = $driver->deleteEntry($entry['object_id']), 'PEAR_Error')) {
        $notification->push(sprintf(_("There was an error deleting this entry: %s"), $deleted->getMessage()), 'horde.error');
    } else {
        $notification->push(sprintf(_("The entry for \"%s\" has been deleted."), $studentdetails[$conf['addresses']['name_field']]), 'horde.success');
        header('Location: ' . Horde_Util::addParameter(Horde::applicationUrl('search.php', true), 'actionID', 'search'));
        exit;
    }
}

// Get tabs.
$url = Horde_Util::addParameter(Horde::applicationUrl('entry.php'), 'entry', $entry['object_id']);
$tabs = new Horde_Core_Ui_Tabs('view', $vars);
$tabs->addTab(_("View"), $url, array('tabname' => 'Entry', 'id' => 'tabEntry'));
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    $tabs->addTab(_("Edit"), $url, array('tabname' => 'EditEntry', 'id' => 'tabEditEntry'));
}
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
    $tabs->addTab(_("Delete"), $url, array('tabname' => 'DeleteEntry', 'id' => 'tabDeleteEntry'));
}

$title = _("Edit Entry");
require SKOLI_TEMPLATES . '/common-header.inc';
require SKOLI_TEMPLATES . '/menu.inc';

echo '<div id="page">';
echo $tabs->render($viewName);
echo '<h1 class="header">' . sprintf(_("Entry for \"%s\""), $studentdetails[$conf['addresses']['name_field']]) . '</h1>';

// View output
switch ($viewName) {
case 'Entry':
    echo $form->renderInactive($form->getRenderer(), $vars);
    break;

case 'EditEntry':
    echo $form->renderActive($form->getRenderer(), $vars, 'entry.php', 'post');
    break;

case 'DeleteEntry':
    require SKOLI_TEMPLATES . '/entry/delete.inc';
    break;
}

echo '</div>';
require $registry->get('templates', 'horde') . '/common-footer.inc';
