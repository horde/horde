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

class Whups_Form_Admin_DeleteReply extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Delete Form Reply Confirmation"));

        $reply = $vars->get('reply');
        $info = $GLOBALS['whups_driver']->getReply($reply);

        $this->addHidden('', 'type', 'int', true, true);
        $this->addHidden('', 'reply', 'int', true, true);
        $pname = &$this->addVariable(
            _("Form Reply Name"), 'reply_name', 'text', false, true);
        $pname->setDefault($info['reply_name']);
        $ptext = &$this->addVariable(
            _("Form Reply Text"), 'reply_text', 'text', false, true);
        $ptext->setDefault($info['reply_text']);
        $this->addVariable(
            _("Really delete this form reply?"), 'yesno', 'enum', true, false,
            null, array(array(0 => _("No"), 1 => _("Yes"))));
    }

}
