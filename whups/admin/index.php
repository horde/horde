<?php
/**
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/../lib/Application.php';
Horde_Registry::appInit('whups', array(
    'permission' => array('whups:admin', Horde_Perms::EDIT)
));

// Set up the page config vars.
$showExtraForm = null;

// Setup vars with all relevant info.
$vars = Horde_Variables::getDefaultVariables();
if (!$vars->exists('action')) {
    $vars->set('action', 'queue');
}

// Admin actions.
$adminurl = Horde::selfUrl(false, false);
$tabs = new Horde_Core_Ui_Tabs('action', $vars);
$tabs->addTab(_("_Edit Queues"), $adminurl, 'queue');
$tabs->addTab(_("Edit _Types"), $adminurl, 'type');
$tabs->addTab(_("Queue/Type Matri_x"), $adminurl, 'mtmatrix');
$tabs->addTab(_("Sen_d Reminders"), $adminurl, 'reminders');

$renderer = new Horde_Form_Renderer();

// start the page
function _open($isopened = false)
{
    global $vars;
    static $opened;

    if ($isopened) {
        return $opened;
    }

    if (is_null($opened)) {
        global $registry, $prefs, $browser, $conf, $notification, $title, $tabs;

        $opened = true;
        $title = _("Administration");
        $GLOBALS['page_output']->header(array(
            'title' => $title
        ));
        require WHUPS_TEMPLATES . '/menu.inc';
        echo $tabs->render($vars->get('action'));
    }
}

function _editStateForms()
{
    global $vars, $renderer, $adminurl;
    _open();
    $form1 = new Whups_Form_Admin_EditStateStepOne($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form2 = new Whups_Form_Admin_DefaultState($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form3 = new Whups_Form_Admin_AddState($vars);
    $form3->renderActive($renderer, $vars, $adminurl, 'post');
}

function _editPriorityForms()
{
    global $vars, $renderer, $adminurl;
    _open();
    $form1 = new Whups_Form_Admin_EditPriorityStepOne($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form2 = new Whups_Form_Admin_DefaultPriority($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form3 = new Whups_Form_Admin_AddPriority($vars);
    $form3->renderActive($renderer, $vars, $adminurl, 'post');
}

switch ($vars->get('formname')) {
case 'whups_form_admin_addtype':
    $form1 = new Whups_Form_Admin_AddType($vars);
    if ($form1->validate($vars)) {
        // First, add the type
        $tid = $whups_driver->addType($vars->get('name'),
                                      $vars->get('description'));
        _open();
        $vars->add('type', $tid);
        $form2 = new Whups_Form_Admin_EditTypeStepTwo($vars);
        $form2->open($renderer, $vars, $adminurl, 'post');

        // render the stage 1 form readonly
        $form1->preserve($vars);
        $renderer->beginInactive(sprintf(_("Add Type %s"), _("- Stage 1")));
        $renderer->renderFormInactive($form1, $vars);
        $renderer->end();

        // render the second stage form
        $renderer->beginActive(sprintf(_("Add Type %s"), _("- Stage 2")));
        $renderer->renderFormActive($form2, $vars);
        $renderer->submit();
        $renderer->end();

        $form2->close($renderer);
    } else {
        _open();
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_edittypestepone':
    $form1 = new Whups_Form_Admin_EditTypeStepOne($vars);
    $vars->set('action', 'type');
    if ($form1->validate($vars)) {
        switch ($vars->get('submitbutton')) {
        case _("Edit Type"):
            _open();
            $form2 = new Whups_Form_Admin_EditTypeStepTwo($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Delete Type"):
            _open();
            $form2 = new Whups_Form_Admin_DeleteType($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Clone Type"):
            _open();
            $form2 = new Whups_Form_Admin_CloneType($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;
        }
    } else {
        _open();
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_clonetype':
    $form = new Whups_Form_Admin_CloneType($vars);
    if ($form->validate($vars)) {
        // Create a new type and copy all attributes of the clone master to
        // the new type.
        $tid = $vars->get('type');
        $type = $whups_driver->getType($tid);
        $states = $whups_driver->getAllStateInfo($tid);
        $priorities = $whups_driver->getAllPriorityInfo($tid);
        $attributes = $whups_driver->getAttributeInfoForType($tid);

        // Create the new type.
        $nid = $ntype = $whups_driver->addType(
            $vars->get('name'), $vars->get('description'));

        // Add the states.
        foreach ($states as $s) {
            $whups_driver->addState(
                $nid, $s['state_name'], $s['state_description'], $s['state_category']);
        }

        // Add the priorities.
        foreach ($priorities as $p) {
            $whups_driver->addPriority(
                $nid, $p['priority_name'], $p['priority_description']);
        }

        // Add attributes.
        foreach ($attributes as $attribute) {
            $a = $whups_driver->getAttributeDesc($attribute['attribute_id']);
            $whups_driver->addAttributeDesc(
                $nid, $a['name'], $a['description'], $a['type'], $a['params'], $a['required']);
        }

        $notification->push(
            sprintf(_("Successfully Cloned %s to %s."), $type['name'], $vars->get('name')),
                    'horde.success');
        Horde::url('admin/?action=type', true)->redirect();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_edittypesteptwo':
    $form = new Whups_Form_Admin_EditTypeStepTwo($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->updateType(
                $vars->get('type'),
                $vars->get('name'),
                $vars->get('description'));
            $notification->push(sprintf(_("The type \"%s\" has been modified."),
                                        $vars->get('name')),
                                'horde.success');
            Horde::url('admin/?action=type', true)->redirect();
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error modifying the type:") . ' ' . $e->getMessage(),
                'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_createdefaultstates':
    $type = $vars->get('type');
    foreach ($conf['states'] as $state) {
        if ($state['active'] == 'active') {
            $whups_driver->addState($type, $state['name'],
                                    $state['desc'], $state['category']);
        }
    }

    _open();
    $form = new Whups_Form_Admin_EditTypeStepTwo($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'whups_form_admin_createdefaultpriorities':
    $type = $vars->get('type');
    foreach ($conf['priorities'] as $priority) {
        if ($priority['active'] == 'active') {
            $whups_driver->addPriority($type, $priority['name'],
                                       $priority['desc']);
        }
    }

    _open();
    $form = new Whups_Form_Admin_EditTypeStepTwo($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'whups_form_admin_deletetype':
    $form = new Whups_Form_Admin_DeleteType($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteType($vars->get('type'));
                $notification->push(
                    _("The type has been deleted."), 'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the type:") . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The type was not deleted."),
                                'horde.message');
        }
        $vars->set('action', 'type');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_addqueue':
    $form = new Whups_Form_Admin_AddQueue($vars);
    if ($form->validate($vars)) {
        try {
            $result = $whups_driver->addQueue(
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('slug'),
                $vars->get('email'));

            $notification->push(
                sprintf(_("The queue \"%s\" has been created."),
                        $vars->get('name')),
                'horde.success');

            _open();
            $vars->set('queue', $result);
            $form2 = new Whups_Form_Admin_EditQueueStepTwo($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the queue:") . ' ' . $e->getMessage(),
                'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editqueuestepone':
    $form1 = new Whups_Form_Admin_EditQueueStepOne($vars);
    if ($form1->validate($vars)) {
        switch ($vars->get('submitbutton')) {
        case _("Edit Queue"):
            _open();
            $form2 = new Whups_Form_Admin_EditQueueStepTwo($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Delete Queue"):
            _open();
            $form2 = new Whups_Form_Admin_DeleteQueue($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;
        }
    } else {
        _open();
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editqueuestepone':
case 'whups_form_admin_editqueuesteptwo':
    $form = new Whups_Form_Admin_EditQueueStepTwo($vars);

    if ($vars->get('formname') == 'whups_form_admin_editqueuesteptwo' &&
        $form->validate($vars)) {
        try {
            $whups_driver->updateQueue(
                $vars->get('queue'),
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('types'),
                $vars->get('versioned'),
                $vars->get('slug'),
                $vars->get('email'),
                $vars->get('default'));
            $notification->push(
                _("The queue has been modified."), 'horde.success');
            _open();
            $form->renderInactive($renderer, $vars);
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the queue:") . ' ' . $e->getMessage(),
                'horde.error');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deletequeue':
    $form = new Whups_Form_Admin_DeleteQueue($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteQueue($vars->get('queue'));
                $notification->push(
                    _("The queue has been deleted."),
                    'horde.success');
            } catch (Horde_Exception $e) {
                $notification->push(
                    _("There was an error deleting the queue:") . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(
                _("The queue was not deleted."),
                'horde.message');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_addstate':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_AddState($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->addState(
                $vars->get('type'),
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('category'));

            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The state \"%s\" has been added to %s."),
                        $vars->get('name'), $typename),
                'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the state:") . ' ' . $e->getMessage(),
                'horde.error');
        }
        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editstatestepone':
    $vars->set('action', 'type');
    if (!$vars->get('submitbutton')) {
        _editStateForms();
    } else {
        _open();
        $form1 = new Whups_Form_Admin_EditStateStepOne($vars);
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit State"):
                $form2 = new Whups_Form_Admin_EditStateStepTwo($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete State"):
                $form2 = new Whups_Form_Admin_DeleteState($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'whups_form_admin_editstatesteptwo':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_EditStateStepTwo($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->updateState(
                $vars->get('state'),
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('category'));

            $notification->push(
                _("The state has been modified."),
                'horde.success');
            _open();
            $form->renderInactive($renderer, $vars);
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the state:") . ' ' . $e->getMessage(),
                'horde.error');
        }
        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_defaultstate':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_DefaultState($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->setDefaultState(
                $vars->get('type'), $vars->get('state'));
            $notification->push(
                _("The default state has been set."),
                'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error setting the default state:") . ' ' . $e->getMessage(),
                'horde.error');
        }
        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deletestate':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_DeleteState($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteState($vars->get('state'));
                $notification->push(
                    _("The state has been deleted."),
                    'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the state:") . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The state was not deleted."),
                                'horde.message');
        }

        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_addpriority':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_AddPriority($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->addPriority(
                $vars->get('type'),
                $vars->get('name'),
                $vars->get('description'));

            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The priority \"%s\" has been added to %s."),
                        $vars->get('name'), $typename),
                'horde.success');
            } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the priority:") . ' ' . $e->getMessage(),
                'horde.error');
            }
        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editprioritystepone':
    $vars->set('action', 'type');
    if (!$vars->get('submitbutton')) {
        _editPriorityForms();
    } else {
        _open();
        $form1 = new Whups_Form_Admin_EditPriorityStepOne($vars);
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Priority"):
                $form2 = new Whups_Form_Admin_EditPriorityStepTwo($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Priority"):
                $form2 = new Whups_Form_Admin_DeletePriority($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'whups_form_admin_editprioritysteptwo':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_EditPriorityStepTwo($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->updatePriority(
                $vars->get('priority'),
                $vars->get('name'),
                $vars->get('description'));

            $notification->push(
                _("The priority has been modified."),
                'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the priority:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_defaultpriority':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_DefaultPriority($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->setDefaultPriority(
                $vars->get('type'), $vars->get('priority'));
            $notification->push(
                _("The default priority has been set."),
                'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error setting the default priority:") . ' ' . $e->getMessage(),
                'horde.error');
        }
        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deletepriority':
    $vars->set('action', 'type');
    $form = new Whups_Form_Admin_DeletePriority($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deletePriority($vars->get('priority'));
                $notification->push(
                    _("The priority has been deleted."),
                    'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the priority:") . ' '
                    . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(
                _("The priority was not deleted."),
                'horde.message');
        }
        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_adduser':
    $form = new Whups_Form_Admin_AddUser($vars);
    if ($form->validate($vars)) {
        $info = $whups_driver->getQueue($vars->get('queue'));
        try {
            $whups_driver->addQueueUser(
                $vars->get('queue'), $vars->get('user'));

            $user = $vars->get('user');
            if (is_array($user)) {
                $userinfo = array();
                foreach ($user as $userID) {
                    $userinfo[] = Whups::formatUser($userID);
                }
                $userinfo = implode(', ', $userinfo);
            } else {
                $userinfo = Whups::formatUser($user);
            }
            $notification->push(
                sprintf(_("%s added to those responsible for \"%s\""),
                        $userinfo, $info['name']),
                'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push(
                sprintf(_("There was an error adding \"%s\" to the responsible list for \"%s\":"),
                        Whups::formatUser($vars->get('user')),
                        $info['name'])
                . ' ' . $e->getMessage(),
                'horde.error');
        }
    }

    _open();
    $form1 = new Whups_Form_Admin_EditUser($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'edituserform':
    $form1 = new Whups_Form_Admin_EditUser($vars);
    $form2 = new Whups_Form_Admin_AddUser($vars);

    _open();

    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';

    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form2 = new Whups_Form_Admin_AddUser($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'whups_form_admin_edituser':
    $form = new Whups_Form_Admin_EditUser($vars);
    if ($form->validate($vars)) {
        $info = $whups_driver->getQueue($vars->get('queue'));
        try {
            $whups_driver->removeQueueUser($vars->get('queue'), $vars->get('user'));

            $notification->push(
                sprintf(_("\"%s\" is no longer among those responsible for \"%s\""),
                        Whups::formatUser($vars->get('user')), $info['name']),
                'horde.success');
        } catch (Whups_Exception $e) {
                $notification->push(
                    sprintf(_("There was an error removing \"%s\" from the responsible list for \"%s\":"),
                            Whups::formatUser($vars->get('user')), $info['name'])
                    . ' ' . $e->getMessage(),
                    'horde.error');
        }
    }

    _open();
    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form = new Whups_Form_Admin_EditUser($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'get');
    $form1 = new Whups_Form_Admin_AddUser($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'get');
    break;

case 'whups_form_admin_addversion':
    $form = new Whups_Form_Admin_AddVersion($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->addVersion(
                $vars->get('queue'),
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('active') == 'on');

            $queuename = $whups_driver->getQueue($vars->get('queue'));
            $queuename = $queuename['name'];
            $notification->push(
                sprintf(_("The version \"%s\" has been added to %s."),
                        $vars->get('name'), $queuename),
                'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the version:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        _open();
        $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
        $form1 = new Whups_Form_Admin_EditVersionStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new Whups_Form_Admin_AddVersion($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editversionstepone':
    $form1 = new Whups_Form_Admin_EditVersionStepOne($vars);

    _open();

    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');

        $form2 = new Whups_Form_Admin_AddVersion($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Version"):
                $form2 = new Whups_Form_Admin_EditVersionStepTwo($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Version"):
                $form2 = new Whups_Form_Admin_DeleteVersion($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'whups_form_admin_editversionsteptwo':
    $form = new Whups_Form_Admin_EditVersionStepTwo($vars);
    if ($form->validate($vars)) {
        try {
            $whups_driver->updateVersion(
                $vars->get('version'),
                $vars->get('name'),
                $vars->get('description'),
                $vars->get('active') == 'on');

            $notification->push(
                _("The version has been modified."),
                'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the version:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        _open();
        $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
        $form1 = new Whups_Form_Admin_EditVersionStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new Whups_Form_Admin_AddVersion($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deleteversion':
    $form = new Whups_Form_Admin_DeleteVersion($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteVersion($vars->get('version'));
                $notification->push(
                    _("The version has been deleted."),
                    'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the version:") . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The version was not deleted."),
                                'horde.message');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditVersionStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new Whups_Form_Admin_AddVersion($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_addattribute':
case 'whups_form_admin_addattribute_reload':
    $form = new Whups_Form_Admin_AddAttribute($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'whups_form_admin_addattribute' &&
        $form->validate($vars)) {

        try {
            $whups_driver->addAttributeDesc(
                $vars->get('type'),
                $vars->get('attribute_name'),
                $vars->get('attribute_description'),
                $vars->get('attribute_type'),
                $vars->get('attribute_params', array()),
                $vars->get('attribute_required'));

            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The attribute \"%s\" has been added to %s."),
                        $vars->get('attribute_name'), $typename),
                'horde.success');
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the attribute:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditAttributeStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new Whups_Form_Admin_AddAttribute($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editattributestepone':
    $form1 = new Whups_Form_Admin_EditAttributeStepOne($vars);
    $vars->set('action', 'type');
    _open();
    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';

        $form2 = new Whups_Form_Admin_AddAttribute($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Attribute"):
                $form2 = new Whups_Form_Admin_EditAttributeStepTwo($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Attribute"):
                $form2 = new DeleteAttributeDescForm($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'whups_form_admin_editattributesteptwo':
case 'whups_form_admin_editattributesteptwo_reload':
    $form = new Whups_Form_Admin_EditAttributeStepTwo($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'whups_form_admin_editattributesteptwo' &&
        $form->validate($vars)) {
        $form->getInfo($vars, $info);
        try {
            $whups_driver->updateAttributeDesc(
                $info['attribute'],
                $info['attribute_name'],
                $info['attribute_description'],
                $info['attribute_type'],
                !empty($info['attribute_params']) ? $info['attribute_params'] : array(),
                $info['attribute_required']);

            $notification->push(
                _("The attribute has been modified."),
                'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
            echo '<br />';
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the attribute:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditAttributeStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new Whups_Form_Admin_AddAttribute($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deleteattribute':
    $form = new DeleteAttributeDescForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteAttributeDesc($vars->get('attribute'));

                $notification->push(
                    _("The attribute has been deleted."),
                    'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the attribute:")
                    . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(
                _("The attribute was not deleted."),
                'horde.message');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditAttributeStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new Whups_From_Admin_AddAttribute($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_addreply':
    $form = new Whups_Form_Admin_AddReply($vars);
    $vars->set('action', 'type');
    if ($form->validate($vars)) {
        try {
            $result = $whups_driver->addReply(
                $vars->get('type'),
                $vars->get('reply_name'),
                $vars->get('reply_text'));

            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The form reply \"%s\" has been added to %s."),
                        $vars->get('reply_name'), $typename),
                'horde.success');
            _open();
            $vars->set('reply', $result);
            $form = new Whups_Form_Admin_EditReplyStepTwo($vars);
            $form->renderInactive($renderer, $vars);
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error creating the form reply:") . ' ' . $e->getMessage(),
                'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_editreplystepone':
    $form1 = new Whups_Form_Admin_EditReplyStepOne($vars);
    $vars->set('action', 'type');
    _open();
    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';

        $form2 = new Whups_Form_Admin_AddReply($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Form Reply"):
                $form2 = new Whups_Form_Admin_EditReplyStepTwo($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Form Reply"):
                $form2 = new Whups_Form_Admin_DeleteReply($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'whups_form_admin_editreplysteptwo':
    $form = new Whups_Form_Admin_EditReplyStepTwo($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'whups_form_admin_editreplysteptwo' &&
        $form->validate($vars)) {
        try {
            $whups_driver->updateReply(
                $vars->get('reply'),
                $vars->get('reply_name'),
                $vars->get('reply_text'));
            $notification->push(
                    _("The form reply has been modified."),
                    'horde.success');
            _open();
            $form->renderInactive($renderer, $vars);
            echo '<br />';
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        } catch (Whups_Exception $e) {
            $notification->push(
                _("There was an error editing the form reply:") . ' ' . $e->getMessage(),
                'horde.error');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditReplyStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new Whups_Form_Admin_AddReply($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_admin_deletereply':
    $form = new Whups_Form_Admin_DeleteReply($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            try {
                $whups_driver->deleteReply($vars->get('reply'));
                $notification->push(
                    _("The form reply has been deleted."),
                    'horde.success');
            } catch (Whups_Exception $e) {
                $notification->push(
                    _("There was an error deleting the form reply:") . ' ' . $e->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(
                _("The form reply was not deleted."),
                'horde.message');
        }

        _open();
        $form1 = new Whups_Form_Admin_EditReplyStepOne($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new Whups_Form_Admin_AddReply($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'whups_form_sendreminder':
    $form = new Whups_Form_SendReminder($vars);
    if ($form->validate($vars)) {
        try {
            Whups::sendReminders($vars);
            $notification->push(_("Reminders were sent."), 'horde.success');
        } catch (Whups_Exception $e) {
            $notification->push($e, 'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'mtmatrix':
    $vars->set('action', 'mtmatrix');
    $queues = $whups_driver->getQueues();
    $types = $whups_driver->getAllTypes();
    $matrix = $vars->get('matrix');

    // Validate data.
    $pairs = array();
    if (!empty($matrix)) {
        foreach ($matrix as $mid => $mtypes) {
            if (isset($queues[$mid])) {
                foreach ($mtypes as $tid => $on) {
                    if (isset($types[$tid])) {
                        $pairs[] = array($mid, $tid);
                    }
                }
            }
        }
    }

    try {
        $whups_driver->updateTypesQueues($pairs);
        $notification->push(
            _("Associations updated successfully."),
            'horde.success');
    } catch (Whups_Exception $e) {
        $notification->push($e, 'horde.error');
    }
    break;
}

if (!_open(true)) {
    // Check for actions.
    switch ($vars->get('action')) {
    case 'type':
        if (count($whups_driver->getAllTypes())) {
            $main1 = new Whups_Form_Admin_EditTypeStepOne($vars);
        }
        $main2 = new Whups_Form_Admin_AddType($vars);
        break;

    case 'reminders':
        $main1 = new Whups_Form_SendReminder($vars);
        break;

    case 'mtmatrix':
        _open();
        $queues = $whups_driver->getQueues();
        $types = $whups_driver->getAllTypes();
        $tlink = Horde::url('admin/?formname=whups_form_admin_edittypestepone');
        $mlink = Horde::url('admin/?formname=whups_form_admin_editqueuestepone');
        require WHUPS_TEMPLATES . '/admin/mtmatrix.inc';
        break;

    case 'queue':
        if (count($whups_driver->getQueues())) {
            $main1 = new Whups_Form_Admin_EditQueueStepOne($vars);
        }
        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            $main2 = new Whups_Form_Admin_AddQueue($vars);
        }
        break;
    }

    _open();
    if (isset($main1)) {
        $main1->renderActive($renderer, $vars, $adminurl, 'get');
    }
    if (isset($main1) && isset($main2)) {
        echo '<br />';
    }
    if (isset($main2)) {
        $main2->renderActive($renderer, $vars, $adminurl, 'get');
    }
}

_open();
$page_output->footer();
