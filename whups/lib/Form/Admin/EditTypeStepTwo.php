<?php
/**
 * This file contains all Horde_Form classes for ticket type administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditTypeStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        global $whups_driver;

        $type = $vars->get('type');
        $info = $whups_driver->getType($type);

        parent::__construct($vars, sprintf(_("Edit %s"), $info['name']));

        $this->addHidden('', 'type', 'int', true, true);

        $tname = &$this->addVariable(_("Type Name"), 'name', 'text', true);
        $tname->setDefault($info['name']);

        $tdesc = &$this->addVariable(
            _("Type Description"), 'description', 'text', true);
        $tdesc->setDefault($info['description']);

        /* States. */
        $states = $whups_driver->getStates($type);
        $tstates = &$this->addVariable(
            _("States for this Type"), 'state', 'set', false, true, null,
            array($states));
        $tstates->setDefault(array_keys($states));
        $statelink = array(
            array('text' => _("Edit States"),
                  'url' => Horde::url('admin/?formname=whups_form_admin_editstatestepone&type=' . $type)));
        if (!count($states)) {
            $statelink[] = array(
                'text' => _("Create Default States"),
                'url' => Horde::url('admin/?formname=whups_form_admin_createdefaultstates&type=' . $type));
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
                  'url' => Horde::url('admin/?formname=whups_form_admin_editprioritystepone&type=' . $type)));
        if (!count($priorities)) {
            $prioritylink[] = array(
                'text' => _("Create Default Priorities"),
                'url' => Horde::url('admin/?formname=whups_form_admin_createdefaultpriorities&type=' . $type));
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
            'url' => Horde::url('admin/?formname=whups_form_admin_editattributestepone&type=' . $type));
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
            'url' => Horde::url('admin/?formname=whups_form_admin_editreplystepone&type=' . $type));
        $this->addVariable('', 'link', 'link', false, true, null,
                           array($replylink));
    }

}
