<?php
/**
 * This file contains all Horde_Form classes for form reply administration.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

class AddReplyForm extends Horde_Form {

    function AddReplyForm(&$vars)
    {
        require_once dirname(__FILE__) . '/../Action.php';

        parent::Horde_Form($vars, _("Add Form Reply"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Form Reply Name"), 'reply_name', 'text', true);
        $this->addVariable(_("Form Reply Text"), 'reply_text', 'longtext', true);
    }

}

class EditReplyStep1Form extends Horde_Form {

    function EditReplyStep1Form(&$vars)
    {
        parent::Horde_Form($vars, _("Edit or Delete Form Replies"));
        $this->setButtons(array(_("Edit Form Reply"), _("Delete Form Reply")));

        $replies = $GLOBALS['whups_driver']->getReplies($vars->get('type'));
        if ($replies) {
            $params = array();
            foreach ($replies as $key => $reply) {
                $params[$key] = $reply['reply_name'];
            }
            $stype = 'enum';
            $type_params = array($params);
        } else {
            $stype = 'invalid';
            $type_params = array(_("There are no form replies to edit"));
        }

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Form Reply Name"), 'reply', $stype, true,
                           false, null, $type_params);
    }

}

class EditReplyStep2Form extends Horde_Form {

    function EditReplyStep2Form(&$vars)
    {
        require_once dirname(__FILE__) . '/../Action.php';

        parent::Horde_Form($vars, _("Edit Form Reply"));

        $reply = $vars->get('reply');
        $info = $GLOBALS['whups_driver']->getReply($reply);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'reply', 'int', true, true);
        $pname = &$this->addVariable(_("Form Reply Name"), 'reply_name',
                                     'text', true);
        $pname->setDefault($info['reply_name']);
        $ptext = &$this->addVariable(_("Form Reply Text"), 'reply_text',
                                     'longtext', true);
        $ptext->setDefault($info['reply_text']);

        /* Permissions link. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => Horde_Perms::EDIT))) {
            $permslink = array(
                'text' => _("Edit the permissions on this form reply"),
                'url' => Horde_Util::addParameter(Horde_Util::addParameter(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php'), 'category', "whups:replies:$reply"), 'autocreate', '1'));
            $this->addVariable('', 'link', 'link', false, true, null,
                               array($permslink));
        }
    }

}

class DeleteReplyForm extends Horde_Form {

    function DeleteReplyForm(&$vars)
    {
        parent::Horde_Form($vars, _("Delete Form Reply Confirmation"));

        $reply = $vars->get('reply');
        $info = $GLOBALS['whups_driver']->getReply($reply);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'reply', 'int', true, true);
        $pname = &$this->addVariable(_("Form Reply Name"), 'reply_name',
                                     'text', false, true);
        $pname->setDefault($info['reply_name']);
        $ptext = &$this->addVariable(_("Form Reply Text"),
                                     'reply_text', 'text', false,
                                     true);
        $ptext->setDefault($info['reply_text']);
        $this->addVariable(_("Really delete this form reply?"), 'yesno', 'enum',
                           true, false, null,
                           array(array(0 => _("No"), 1 => _("Yes"))));
    }

}
