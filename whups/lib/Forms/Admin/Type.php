<?php
/**
 * This file contains all Horde_Form classes for ticket type administration.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class AddTypeStep1Form extends Horde_Form {

    function AddTypeStep1Form(&$vars)
    {
        parent::Horde_Form($vars, _("Add Type"));
        $this->appendButtons(_("Add Type"));
        $this->addVariable(_("Type Name"), 'name', 'text', true);
        $this->addVariable(_("Type Description"), 'description', 'text', true);
    }

}

class EditTypeStep1Form extends Horde_Form {

    function EditTypeStep1Form(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Edit or Delete Types"));
        $this->setButtons(array(_("Edit Type"),
                                _("Clone Type"),
                                _("Delete Type")));

        $types = $whups_driver->getAllTypes();
        if ($types) {
            $ttype = 'enum';
            $type_params = array($types);
        } else {
            $ttype = 'invalid';
            $type_params = array(_("There are no types to edit"));
        }

        $this->addVariable(_("Type Name"), 'type', $ttype, true, false, null,
                           $type_params);
    }

}

class EditTypeStep2Form extends Horde_Form {

    function EditTypeStep2Form(&$vars)
    {
        global $whups_driver;

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);

        parent::Horde_Form($vars, sprintf(_("Edit %s"), $info['name']));

        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(_("Type Name"), 'name', 'text', true);
        $tname->setDefault($info['name']);

        $tdesc = &$this->addVariable(_("Type Description"), 'description',
                                     'text', true);
        $tdesc->setDefault($info['description']);

        /* States. */
        $states = $whups_driver->getStates($type);
        $tstates = &$this->addVariable(_("States for this Type"), 'state',
                                       'set', false, true, null,
                                       array($states));
        $tstates->setDefault(array_keys($states));
        $statelink = array(
            array('text' => _("Edit States"),
                  'url' => Horde::url('admin/?formname=editstatestep1form&type=' . $type)));
        if (!count($states)) {
            $statelink[] = array(
                'text' => _("Create Default States"),
                'url' => Horde::url('admin/?formname=createdefaultstates&type=' . $type));
        }
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($statelink));

        /* Priorities. */
        $priorities = $whups_driver->getPriorities($type);
        $tpriorities = &$this->addVariable(_("Priorities for this Type"),
                                           'priority', 'set', false, true, null,
                                           array($priorities));
        $tpriorities->setDefault(array_keys($priorities));
        $prioritylink = array(
            array('text' => _("Edit Priorities"),
                  'url' => Horde::url('admin/?formname=editprioritystep1form&type=' . $type)));
        if (!count($priorities)) {
            $prioritylink[] = array(
                'text' => _("Create Default Priorities"),
                'url' => Horde::url('admin/?formname=createdefaultpriorities&type=' . $type));
        }
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($prioritylink));

        /* Attributes. */
        $attributes = $whups_driver->getAttributesForType($type);
        $params = array();
        foreach ($attributes as $key => $attribute) {
            $params[$key] = $attribute['human_name'];
        }
        $tattributes = &$this->addVariable(_("Attributes for this Type"),
                                           'attribute', 'set', false, true,
                                           null, array($params));
        $tattributes->setDefault(array_keys($attributes));
        $attributelink = array(
            'text' => _("Edit Attributes"),
            'url' => Horde::url('admin/?formname=editattributedescstep1form&type=' . $type));
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($attributelink));

        /* Form replies. */
        $replies = $whups_driver->getReplies($type);
        $params = array();
        foreach ($replies as $key => $reply) {
            $params[$key] = $reply['reply_name'];
        }
        $treplies = &$this->addVariable(_("Form Replies for this Type"),
                                        'reply', 'set', false, true, null,
                                        array($params));
        $treplies->setDefault(array_keys($replies));
        $replylink = array(
            'text' => _("Edit Form Replies"),
            'url' => Horde::url('admin/?formname=editreplystep1form&type=' . $type));
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($replylink));
    }

}

class DeleteTypeForm extends Horde_Form {

    function DeleteTypeForm(&$vars)
    {
        global $whups_driver;

        parent::Horde_Form($vars, _("Delete Type Confirmation"));

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);

        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(_("Type Name"), 'name', 'text', false,
                                     true);
        $tname->setDefault($info['name']);

        $tdesc = &$this->addVariable(_("Type Description"), 'description',
                                     'text', false, true);
        $tdesc->setDefault($info['description']);

        $states = $whups_driver->getStates($type);
        $tstates = &$this->addVariable(_("States for this Type"), 'state',
                                       'set', false, true, null,
                                       array($states));
        $tstates->setDefault(array_keys($states));

        $priorities = $whups_driver->getPriorities($type);
        $tpriorities = &$this->addVariable(_("Priorities for this Type"),
                                           'priority', 'set', false, true, null,
                                           array($priorities));
        $tpriorities->setDefault(array_keys($priorities));

        $yesno = array(array(0 => _("No"), 1 => _("Yes")));
        $this->addVariable(
            _("Really delete this type? This may cause data problems!"),
            'yesno', 'enum', true, false, null, $yesno);
    }

}

class CloneTypeForm extends Horde_Form {

    function CloneTypeForm(&$vars)
    {
        global $whups_driver;

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);
        parent::Horde_Form($vars,
                           sprintf(_("Make a clone of %s"), $info['name']));

        $this->setButtons(_("Clone"));
        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(_("Name of the cloned copy"), 'name',
                                     'text', true);
        $tname->setDefault(sprintf(_("Copy of %s"), $info['name']));

        $tdesc = &$this->addVariable(_("Clone Description"), 'description',
                                     'text', true);
        $tdesc->setDefault($info['description']);
    }

}
