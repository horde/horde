<?php
/**
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('whups');

if (!$registry->isAdmin(array('permission' => 'whups:admin'))) {
    $registry->authenticateFailure('whups', $e);
}

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
        require WHUPS_TEMPLATES . '/common-header.inc';
        require WHUPS_TEMPLATES . '/menu.inc';
        echo $tabs->render($vars->get('action'));
    }
}

function _editStateForms()
{
    global $vars, $renderer, $adminurl;
    _open();
    $form1 = new EditStateStep1Form($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form2 = new DefaultStateForm($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form3 = new AddStateForm($vars);
    $form3->renderActive($renderer, $vars, $adminurl, 'post');
}

function _editPriorityForms()
{
    global $vars, $renderer, $adminurl;
    _open();
    $form1 = new EditPriorityStep1Form($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form2 = new DefaultPriorityForm($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $form3 = new AddPriorityForm($vars);
    $form3->renderActive($renderer, $vars, $adminurl, 'post');
}

switch ($vars->get('formname')) {
case 'addtypestep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form1 = new AddTypeStep1Form($vars);
    if ($form1->validate($vars)) {
        // First, add the type
        $tid = $whups_driver->addType($vars->get('name'),
                                      $vars->get('description'));
        if ($tid instanceof PEAR_Error) {
            throw new Horde_Exception($tid);
        }

        _open();
        $vars->add('type', $tid);
        $form2 = new EditTypeStep2Form($vars);
        $form2->title = 'addtypestep2form';
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

case 'addtypestep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form1 = new AddTypeStep1Form($vars);
    $form2 = new EditTypeStep2Form($vars);
    $form2->_name = 'addtypestep2form';
    break;

case 'edittypestep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form1 = new EditTypeStep1Form($vars);
    $vars->set('action', 'type');
    if ($form1->validate($vars)) {
        switch ($vars->get('submitbutton')) {
        case _("Edit Type"):
            _open();
            $form2 = new EditTypeStep2Form($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Delete Type"):
            _open();
            $form2 = new DeleteTypeForm($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Clone Type"):
            _open();
            $form2 = new CloneTypeForm($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;
        }
    } else {
        _open();
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'clonetypeform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form = new CloneTypeForm($vars);
    if ($form->validate($vars)) {
        // Create a new type and copy all attributes of the clone master to
        // the new type.
        $tid = $vars->get('type');
        $type = $whups_driver->getType($tid);
        $states = $whups_driver->getAllStateInfo($tid);
        $priorities = $whups_driver->getAllPriorityInfo($tid);
        $attributes = $whups_driver->getAttributeInfoFortype($tid);

        // Create the new type.
        $nid = $ntype = $whups_driver->addType($vars->get('name'),
                                               $vars->get('description'));

        // Add the states.
        foreach ($states as $s) {
            $whups_driver->addState($nid, $s['state_name'],
                                    $s['state_description'],
                                    $s['state_category']);
        }

        // Add the priorities.
        foreach ($priorities as $p) {
            $whups_driver->addPriority($nid, $p['priority_name'],
                                       $p['priority_description']);
        }

        // Add attributes.
        foreach ($attributes as $a) {
            $whups_driver->addAttributeDesc($nid, $a['attribute_name'],
                                            $a['attribute_description']);
        }

        $notification->push(sprintf(_("Successfully Cloned %s to %s."),
                                    $type['name'], $vars->get('name')),
                            'horde.success');
        Horde::applicationUrl('admin/?action=type', true)->redirect();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'edittypeform':
case 'edittypestep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form = new EditTypeStep2Form($vars);
    if ($vars->get('formname') == 'edittypestep2form' &&
        $form->validate($vars)) {
        $result = $whups_driver->updateType($vars->get('type'),
                                            $vars->get('name'),
                                            $vars->get('description'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(_("There was an error modifying the type:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        } else {
            $notification->push(sprintf(_("The type \"%s\" has been modified."),
                                        $vars->get('name')),
                                'horde.success');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'createdefaultstates':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $type = $vars->get('type');
    foreach ($conf['states'] as $state) {
        if ($state['active'] == 'active') {
            $whups_driver->addState($type, $state['name'],
                                    $state['desc'], $state['category']);
        }
    }

    _open();
    $form = new EditTypeStep2Form($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'createdefaultpriorities':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $type = $vars->get('type');
    foreach ($conf['priorities'] as $priority) {
        if ($priority['active'] == 'active') {
            $whups_driver->addPriority($type, $priority['name'],
                                       $priority['desc']);
        }
    }

    _open();
    $form = new EditTypeStep2Form($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'deletetypeform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
    $form = new DeleteTypeForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteType($vars->get('type'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The type has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(_("There was an error deleting the type:")
                                    . ' ' . $result->getMessage(),
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

case 'addqueueform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Queue.php';
    $form = new AddQueueForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->addQueue($vars->get('name'),
                                          $vars->get('description'),
                                          $vars->get('slug'),
                                          $vars->get('email'));
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(
                sprintf(_("The queue \"%s\" has been created."),
                        $vars->get('name')),
                'horde.success');

            _open();
            $vars->set('queue', $result);
            $form2 = new EditQueueStep2Form($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
        } else {
            $notification->push(_("There was an error creating the queue:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editqueuestep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Queue.php';
    $form1 = new EditQueueStep1Form($vars);
    if ($form1->validate($vars)) {
        switch ($vars->get('submitbutton')) {
        case _("Edit Queue"):
            _open();
            $form2 = new EditQueueStep2Form($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;

        case _("Delete Queue"):
            _open();
            $form2 = new DeleteQueueForm($vars);
            $form2->renderActive($renderer, $vars, $adminurl, 'post');
            break;
        }
    } else {
        _open();
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editqueueform':
case 'editqueuestep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Queue.php';
    $form = new EditQueueStep2Form($vars);

    if ($vars->get('formname') == 'editqueuestep2form' &&
        $form->validate($vars)) {
        $result = $whups_driver->updateQueue($vars->get('queue'),
                                             $vars->get('name'),
                                             $vars->get('description'),
                                             $vars->get('types'),
                                             $vars->get('versioned'),
                                             $vars->get('slug'),
                                             $vars->get('email'),
                                             $vars->get('default'));
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(_("The queue has been modified."),
                                'horde.success');
            $perms = $GLOBALS['injector']->getInstance('Horde_Perms');
            if (!$perms->exists('whups:queues:' . $vars->get('queue') . ':update')) {
                $p = $perms->newPermission('whups:queues:'
                                            . $vars->get('queue') . ':update');
                $perms->addPermission($p);
            }
            if (!$perms->exists('whups:queues:' . $vars->get('queue') . ':assign')) {
                $p = $perms->newPermission('whups:queues:'
                                            . $vars->get('queue') . ':assign');
                $perms->addPermission($p);
            }

            _open();
            $form->renderInactive($renderer, $vars);
        } else {
            $notification->push(_("There was an error editing the queue:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deletequeueform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Queue.php';
    $form = new DeleteQueueForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteQueue($vars->get('queue'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push( _("The queue has been deleted."),
                                     'horde.success');
            } else {
                $notification->push(_("There was an error deleting the queue:")
                                    . ' ' . $result->getMessage(),
                                    'horde.error');
            }
        } else {
            $notification->push(_("The queue was not deleted."),
                                'horde.message');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'addstateform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/State.php';
    $vars->set('action', 'type');
    $form = new AddStateForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->addState($vars->get('type'),
                                          $vars->get('name'),
                                          $vars->get('description'),
                                          $vars->get('category'));
        if (!is_a($result, 'PEAR_Error')) {
            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The state \"%s\" has been added to %s."),
                        $vars->get('name'), $typename),
                'horde.success');
        } else {
            $notification->push(_("There was an error creating the state:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editstatestep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/State.php';
    $vars->set('action', 'type');
    if (!$vars->get('submitbutton')) {
        _editStateForms();
    } else {
        _open();
        $form1 = new EditStateStep1Form($vars);
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit State"):
                $form2 = new EditStateStep2Form($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete State"):
                $form2 = new DeleteStateForm($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'editstatestep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/State.php';
    $vars->set('action', 'type');
    $form = new EditStateStep2Form($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->updateState($vars->get('state'),
                                             $vars->get('name'),
                                             $vars->get('description'),
                                             $vars->get('category'));
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(_("The state has been modified."),
                                'horde.success');
            _open();
            $form->renderInactive($renderer, $vars);
        } else {
            $notification->push(_("There was an error editing the state:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'defaultstateform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/State.php';
    $vars->set('action', 'type');
    $form = new DefaultStateForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->setDefaultState($vars->get('type'),
                                                 $vars->get('state'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(
                _("There was an error setting the default state:") . ' '
                . $result->getMessage(),
                'horde.error');
        } else {
            $notification->push(_("The default state has been set."),
                                'horde.success');
        }

        _editStateForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deletestateform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/State.php';
    $vars->set('action', 'type');
    $form = new DeleteStateForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteState($vars->get('state'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The state has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(_("There was an error deleting the state:")
                                    . ' ' . $result->getMessage(),
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

case 'addpriorityform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Priority.php';
    $vars->set('action', 'type');
    $form = new AddPriorityForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->addPriority($vars->get('type'),
                                             $vars->get('name'),
                                             $vars->get('description'));
        if (!is_a($result, 'PEAR_Error')) {
            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The priority \"%s\" has been added to %s."),
                        $vars->get('name'), $typename),
                'horde.success');
        } else {
            $notification->push(
                _("There was an error creating the priority:") . ' '
                . $result->getMessage(),
                'horde.error');
        }

        $vars = new Horde_Variables(array('type' => $vars->get('type')));
        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editprioritystep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Priority.php';
    $vars->set('action', 'type');
    if (!$vars->get('submitbutton')) {
        _editPriorityForms();
    } else {
        _open();
        $form1 = new EditPriorityStep1Form($vars);
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Priority"):
                $form2 = new EditPriorityStep2Form($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Priority"):
                $form2 = new DeletePriorityForm($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'editprioritystep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Priority.php';
    $vars->set('action', 'type');
    $form = new EditPriorityStep2Form($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->updatePriority($vars->get('priority'),
                                                $vars->get('name'),
                                                $vars->get('description'));
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(_("The priority has been modified."),
                                'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
        } else {
            $notification->push(_("There was an error editing the priority:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        $vars = new Horde_Variables(array('type' => $vars->get('type')));

        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'defaultpriorityform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Priority.php';
    $vars->set('action', 'type');
    $form = new DefaultPriorityForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->setDefaultPriority($vars->get('type'),
                                                    $vars->get('priority'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(
                _("There was an error setting the default priority:") . ' '
                . $result->getMessage(),
                'horde.error');
        } else {
            $notification->push(_("The default priority has been set."),
                                'horde.success');
        }

        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deletepriorityform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Priority.php';
    $vars->set('action', 'type');
    $form = new DeletePriorityForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deletePriority($vars->get('priority'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The priority has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(
                    _("There was an error deleting the priority:") . ' '
                    . $result->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The priority was not deleted."),
                                'horde.message');
        }

        _editPriorityForms();
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'adduserform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/User.php';
    $form = new AddUserForm($vars);
    if ($form->validate($vars)) {
        $info = $whups_driver->getQueue($vars->get('queue'));
        $result = $whups_driver->addQueueUser($vars->get('queue'),
                                              $vars->get('user'));
        if (!is_a($result, 'PEAR_Error')) {
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
        } else {
            $notification->push(
                sprintf(_("There was an error adding \"%s\" to the responsible list for \"%s\":"),
                        Whups::formatUser($vars->get('user')),
                        $info['name'])
                . ' ' . $result->getMessage(),
                'horde.error');
        }
    }

    _open();
    $form1 = new EditUserStep1Form($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';
    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'edituserform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/User.php';
    $form1 = new EditUserStep1Form($vars);
    $form2 = new AddUserForm($vars);

    _open();

    $form1->renderActive($renderer, $vars, $adminurl, 'post');
    echo '<br />';

    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form2 = new AddUserForm($vars);
    $form2->renderActive($renderer, $vars, $adminurl, 'post');
    break;

case 'edituserstep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/User.php';
    $form = new EditUserStep1Form($vars);
    if ($form->validate($vars)) {
        $info = $whups_driver->getQueue($vars->get('queue'));
        $result = $whups_driver->removeQueueUser($vars->get('queue'),
                                                 $vars->get('user'));
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(
                sprintf(_("\"%s\" is no longer among those responsible for \"%s\""),
                        Whups::formatUser($vars->get('user')), $info['name']),
                'horde.success');
        } else {
            $notification->push(
                sprintf(_("There was an error removing \"%s\" from the responsible list for \"%s\":"),
                        Whups::formatUser($vars->get('user')), $info['name'])
                . ' ' . $result->getMessage(),
                'horde.error');
        }
    }

    _open();
    $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
    $form = new EditUserStep1Form($vars);
    $form->renderActive($renderer, $vars, $adminurl, 'get');
    $form1 = new AddUserForm($vars);
    $form1->renderActive($renderer, $vars, $adminurl, 'get');
    break;

case 'addversionform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Version.php';
    $form = new AddVersionForm($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->addVersion($vars->get('queue'),
                                            $vars->get('name'),
                                            $vars->get('description'),
                                            $vars->get('active') == 'on');
        if (!is_a($result, 'PEAR_Error')) {
            $queuename = $whups_driver->getQueue($vars->get('queue'));
            $queuename = $queuename['name'];
            $notification->push(
                sprintf(_("The version \"%s\" has been added to %s."),
                        $vars->get('name'), $queuename),
                'horde.success');
        } else {
            $notification->push(_("There was an error creating the version:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        _open();
        $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
        $form1 = new EditVersionStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new AddVersionForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editversionstep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Version.php';
    $form1 = new EditVersionStep1Form($vars);

    _open();

    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');

        $form2 = new AddVersionForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Version"):
                $form2 = new EditVersionStep2Form($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Version"):
                $form2 = new DeleteVersionForm($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'editversionstep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Version.php';
    $form = new EditVersionStep2Form($vars);
    if ($form->validate($vars)) {
        $result = $whups_driver->updateVersion($vars->get('version'),
                                               $vars->get('name'),
                                               $vars->get('description'),
                                               $vars->get('active') == 'on');
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push(_("The version has been modified."),
                                'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
        } else {
            $notification->push(_("There was an error editing the version:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        _open();
        $vars = new Horde_Variables(array('queue' => $vars->get('queue')));
        $form1 = new EditVersionStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new AddVersionForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deleteversionform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Version.php';
    $form = new DeleteVersionForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteVersion($vars->get('version'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The version has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(_("There was an error deleting the version:")
                                    . ' ' . $result->getMessage(),
                                    'horde.error');
            }
        } else {
            $notification->push(_("The version was not deleted."),
                                'horde.message');
        }

        _open();
        $form1 = new EditVersionStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new AddVersionForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'addattributedescform':
case 'addattributedescform_reload':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Attribute.php';
    $form = new AddAttributeDescForm($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'addattributedescform' &&
        $form->validate($vars)) {
        $result = $whups_driver->addAttributeDesc(
            $vars->get('type'),
            $vars->get('attribute_name'),
            $vars->get('attribute_description'),
            $vars->get('attribute_type'),
            $vars->get('attribute_params'),
            $vars->get('attribute_required'));
        if (!is_a($result, 'PEAR_Error')) {
            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The attribute \"%s\" has been added to %s."),
                        $vars->get('attribute_name'), $typename),
                'horde.success');
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        } else {
            $notification->push(_("There was an error creating the attribute:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        _open();
        $form1 = new EditAttributeDescStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new AddAttributeDescForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editattributedescstep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Attribute.php';
    $form1 = new EditAttributeDescStep1Form($vars);
    $vars->set('action', 'type');
    _open();
    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';

        $form2 = new AddAttributeDescForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Attribute"):
                $form2 = new EditAttributeDescStep2Form($vars);
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

case 'editattributedescstep2form':
case 'editattributedescstep2form_reload':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Attribute.php';
    $form = new EditAttributeDescStep2Form($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'editattributedescstep2form' &&
        $form->validate($vars)) {
        $form->getInfo($vars, $info);
        $result = $whups_driver->updateAttributeDesc(
            $info['attribute'],
            $info['attribute_name'],
            $info['attribute_description'],
            $info['attribute_type'],
            $info['attribute_params'],
            $info['attribute_required']);
        if (!is_a($result, 'PEAR_Error')) {
            $notification->push( _("The attribute has been modified."),
                                 'horde.success');

            _open();
            $form->renderInactive($renderer, $vars);
            echo '<br />';
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        } else {
            $notification->push(_("There was an error editing the attribute:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        }

        _open();
        $form1 = new EditAttributeDescStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new AddAttributeDescForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deleteattributedescform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Attribute.php';
    $form = new DeleteAttributeDescForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteAttributeDesc($vars->get('attribute'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The attribute has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(
                    _("There was an error deleting the attribute:")
                    . ' ' . $result->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The attribute was not deleted."),
                                'horde.message');
        }

        _open();
        $form1 = new EditAttributeDescStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        $form2 = new AddAttributeDescForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'addreplyform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Reply.php';
    $form = new AddReplyForm($vars);
    $vars->set('action', 'type');
    if ($form->validate($vars)) {
        $result = $whups_driver->addReply(
            $vars->get('type'),
            $vars->get('reply_name'),
            $vars->get('reply_text'));
        if (!is_a($result, 'PEAR_Error')) {
            $typename = $whups_driver->getType($vars->get('type'));
            $typename = $typename['name'];
            $notification->push(
                sprintf(_("The form reply \"%s\" has been added to %s."),
                        $vars->get('reply_name'), $typename),
                'horde.success');
            _open();
            $vars->set('reply', $result);
            $form = new EditReplyStep2Form($vars);
            $form->renderInactive($renderer, $vars);
        } else {
            $notification->push(_("There was an error creating the form reply:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        }
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'editreplystep1form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Reply.php';
    $form1 = new EditReplyStep1Form($vars);
    $vars->set('action', 'type');
    _open();
    if (!$vars->get('submitbutton')) {
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';

        $form2 = new AddReplyForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        if ($form1->validate($vars)) {
            switch ($vars->get('submitbutton')) {
            case _("Edit Form Reply"):
                  $form2 = new EditReplyStep2Form($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;

            case _("Delete Form Reply"):
                $form2 = new DeleteReplyForm($vars);
                $form2->renderActive($renderer, $vars, $adminurl, 'post');
                break;
            }
        } else {
            $form1->renderActive($renderer, $vars, $adminurl, 'post');
        }
    }
    break;

case 'editreplystep2form':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Reply.php';
    $form = new EditReplyStep2Form($vars);
    $vars->set('action', 'type');
    if ($vars->get('formname') == 'editreplystep2form' &&
        $form->validate($vars)) {
        $result = $whups_driver->updateReply(
            $vars->get('reply'),
            $vars->get('reply_name'),
            $vars->get('reply_text'));
        if (is_a($result, 'PEAR_Error')) {
            $notification->push(_("There was an error editing the form reply:")
                                . ' ' . $result->getMessage(),
                                'horde.error');
        } else {
            $notification->push( _("The form reply has been modified."),
                                 'horde.success');
            _open();
            $form->renderInactive($renderer, $vars);
            echo '<br />';
            $vars = new Horde_Variables(array('type' => $vars->get('type')));
        }

        _open();
        $form1 = new EditReplyStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new AddReplyForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'deletereplyform':
    require_once WHUPS_BASE . '/lib/Forms/Admin/Reply.php';
    $form = new DeleteReplyForm($vars);
    if ($form->validate($vars)) {
        if ($vars->get('yesno') == 1) {
            $result = $whups_driver->deleteReply($vars->get('reply'));
            if (!is_a($result, 'PEAR_Error')) {
                $notification->push(_("The form reply has been deleted."),
                                    'horde.success');
            } else {
                $notification->push(
                    _("There was an error deleting the form reply:")
                    . ' ' . $result->getMessage(),
                    'horde.error');
            }
        } else {
            $notification->push(_("The form reply was not deleted."),
                                'horde.message');
        }

        _open();
        $form1 = new EditReplyStep1Form($vars);
        $form1->renderActive($renderer, $vars, $adminurl, 'post');
        echo '<br />';
        $form2 = new AddReplyForm($vars);
        $form2->renderActive($renderer, $vars, $adminurl, 'post');
    } else {
        _open();
        $form->renderActive($renderer, $vars, $adminurl, 'post');
    }
    break;

case 'sendreminderform':
    require_once WHUPS_BASE . '/lib/Forms/Admin.php';
    $form = new SendReminderForm($vars);
    if ($form->validate($vars)) {
        $result = Whups::sendReminders($vars);
        if (is_a($result, 'PEAR_Error')) {
            $notification->push($result, 'horde.error');
            _open();
            $form->renderActive($renderer, $vars, $adminurl, 'post');
        } else {
            $notification->push(_("Reminders were sent."), 'horde.success');
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

    $result = $whups_driver->updateTypesQueues($pairs);
    if (is_a($result, 'PEAR_Error')) {
        $notification->push($result, 'horde.error');
    } else {
        $notification->push(_("Associations updated successfully."),
                            'horde.success');
    }
    break;
}

if (!_open(true)) {
    // Check for actions.
    switch ($vars->get('action')) {
    case 'type':
        require_once WHUPS_BASE . '/lib/Forms/Admin/Type.php';
        if (count($whups_driver->getAllTypes())) {
            $main1 = new EditTypeStep1Form($vars);
        }
        $main2 = new AddTypeStep1Form($vars);
        break;

    case 'reminders':
        require_once WHUPS_BASE . '/lib/Forms/Admin.php';
        $main1 = new SendReminderForm($vars);
        break;

    case 'mtmatrix':
        _open();
        $queues = $whups_driver->getQueues();
        $types = $whups_driver->getAllTypes();
        $tlink = Horde::applicationUrl('admin/?formname=edittypeform');
        $mlink = Horde::applicationUrl('admin/?formname=editqueueform');
        require WHUPS_TEMPLATES . '/admin/mtmatrix.inc';
        break;

    case 'queue':
        require_once WHUPS_BASE . '/lib/Forms/Admin/Queue.php';
        if (count($whups_driver->getQueues())) {
            $main1 = new EditQueueStep1Form($vars);
        }
        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            $main2 = new AddQueueForm($vars);
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
require $registry->get('templates', 'horde') . '/common-footer.inc';
