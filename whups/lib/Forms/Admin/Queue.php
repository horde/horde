<?php
/**
 * This file contains all Horde_Form classes for queue administration.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddQueueForm extends Horde_Form {

    function AddQueueForm(&$vars)
    {
        parent::Horde_Form($vars, _("Add Queue"));
        $this->appendButtons(_("Add Queue"));

        $this->addVariable(_("Queue Name"), 'name', 'text', true);
        $this->addVariable(_("Queue Description"), 'description', 'text', true);
        $this->addVariable(
            _("Queue Slug"), 'slug', 'text', false, false,
            sprintf(_("Slugs allows direct access to this queue's open tickets by visiting: %s. <br /> Slug names may contain only letters, numbers or the _ (underscore) character."),
                    Horde::url('queue/slugname', true)),
            array('/^[a-zA-Z1-9_]*$/'));
        $this->addVariable(_("Queue Email"), 'email', 'email', false, false,
                           _("This email address will be used when sending notifications for any queue tickets."));
    }

}

class EditQueueStep1Form extends Horde_Form {

    function EditQueueStep1Form(&$vars)
    {
        global $whups_driver, $registry;

        if ($registry->hasMethod('tickets/listQueues') == $registry->getApp()) {
            parent::Horde_Form($vars, _("Edit or Delete Queues"));
            $this->setButtons(array(_("Edit Queue"), _("Delete Queue")));
        } else {
            parent::Horde_Form($vars, _("Edit Queues"));
            $this->setButtons(array(_("Edit Queue")));
        }

        $queues = Whups::permissionsFilter($whups_driver->getQueues(), 'queue',
                                           Horde_Perms::EDIT);
        if ($queues) {
            $modtype = 'enum';
            $type_params = array($queues);
        } else {
            $modtype = 'invalid';
            $type_params = array(_("There are no queues to edit"));
        }

        $this->addVariable(_("Queue Name"), 'queue', $modtype, true, false,
                           null, $type_params);
    }

}

class EditQueueStep2Form extends Horde_Form {

    function EditQueueStep2Form(&$vars)
    {
        global $whups_driver, $registry;

        parent::Horde_Form($vars);

        $queue = $vars->get('queue');
        $info = $whups_driver->getQueue($queue);
        if (is_a($info, 'PEAR_Error')) {
            $this->addVariable(_("Invalid Queue"), 'invalid', 'invalid', true,
                               false, null, array(_("Invalid Queue")));
            return;
        }

        $this->setTitle(sprintf(_("Edit %s"), $info['name']));
        $this->addHidden('', 'queue', 'int', true, true);

        $mname = &$this->addVariable(_("Queue Name"), 'name', 'text', true,
                                     $info['readonly']);
        $mname->setDefault($info['name']);

        $mdesc = &$this->addVariable(_("Queue Description"), 'description',
                                     'text', true, $info['readonly']);
        $mdesc->setDefault($info['description']);

        $mslug = &$this->addVariable(_("Queue Slug"), 'slug', 'text', false,
                                     $info['readonly']);
        $mslug->setDefault($info['slug']);

        $memail = &$this->addVariable(_("Queue Email"), 'email', 'email',
                                      false, $info['readonly']);
        $memail->setDefault($info['email']);

        $types = $whups_driver->getAllTypes();
        $mtypes = &$this->addVariable(
            _("Ticket Types associated with this Queue"), 'types', 'set', true,
            false, null, array($types));
        $mtypes->setDefault(array_keys($whups_driver->getTypes($queue)));
        $mdefaults = &$this->addVariable(_("Default Ticket Type"), 'default',
                                         'enum', false, false, null,
                                         array($types));
        $mdefaults->setDefault($whups_driver->getDefaultType($queue));

        /* Versioned and version link. */
        $mversioned = &$this->addVariable(
            _("Keep a set of versions for this queue?"), 'versioned',
            'boolean', false, $info['readonly']);
        $mversioned->setDefault($info['versioned']);
        if ($registry->hasMethod('tickets/listVersions') == $registry->getApp()) {
            $versionlink = array(
                'text' => _("Edit the versions for this queue"),
                'url' => Horde_Util::addParameter(Horde::url('admin/?formname=editversionstep1form'), 'queue', $queue));
            $this->addVariable('', 'link', 'link', false, true, null,
                               array($versionlink));
        }

        /* Usertype and usertype link. */
        $users = $whups_driver->getQueueUsers($queue);
        $f_users = array();
        foreach ($users as $user) {
            $f_users[$user] = Whups::formatUser($user);
        }
        asort($f_users);
        $musers = &$this->addVariable(_("Users responsible for this Queue"),
                                      'users', 'set', false, true, null,
                                      array($f_users));
        $musers->setDefault($whups_driver->getQueueUsers($queue));
        $userlink = array(
            'text' => _("Edit the users responsible for this queue"),
            'url' => Horde_Util::addParameter(Horde::url('admin/?formname=edituserform'), 'queue', $queue));
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($userlink));

        /* Permissions link. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => Horde_Perms::EDIT))) {
            $permslink = array(
                'text' => _("Edit the permissions on this queue"),
                'url' => Horde_Util::addParameter(Horde_Util::addParameter(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php'), 'category', "whups:queues:$queue"), 'autocreate', '1'));
            $this->addVariable('', 'link', 'link', false, true, null,
                               array($permslink));
        }
    }

}

class DeleteQueueForm extends Horde_Form {

    function DeleteQueueForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Delete Queue Confirmation"));

        $queue = $vars->get('queue');
        $info = $whups_driver->getQueue($queue);

        $this->addHidden('', 'queue', 'int', true, true);

        $mname = &$this->addVariable(_("Queue Name"), 'name', 'text', false,
                                     true);
        $mname->setDefault($info['name']);

        $mdesc = &$this->addVariable(_("Queue Description"), 'description',
                                     'text', false, true);
        $mdesc->setDefault($info['description']);

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(
            _("Really delete this queue? This will also delete all associated tickets and their comments. This can not be undone!"),
            'yesno', 'enum', true, false, null, $yesno);
    }

}
