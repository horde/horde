<?php
/**
 * This file contains all Horde_Form classes for form reply administration.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_EditReplyStepTwo extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit Form Reply"));

        $reply = $vars->get('reply');
        $info = $GLOBALS['whups_driver']->getReply($reply);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'reply', 'int', true, true);
        $pname = &$this->addVariable(
            _("Form Reply Name"), 'reply_name', 'text', true);
        $pname->setDefault($info['reply_name']);
        $ptext = &$this->addVariable(
            _("Form Reply Text"), 'reply_text', 'longtext', true);
        $ptext->setDefault($info['reply_text']);

        /* Permissions link. */
        if ($GLOBALS['registry']->isAdmin(array('permission' => 'whups:admin', 'permlevel' => Horde_Perms::EDIT))) {
            $permslink = array(
                'text' => _("Edit the permissions on this form reply"),
                'url' => Horde_Util::addParameter(Horde_Util::addParameter(Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/admin/perms/edit.php'), 'category', "whups:replies:$reply"), 'autocreate', '1'));
            $this->addVariable('', 'link', 'link', false, true, null, array($permslink));
        }
    }

}