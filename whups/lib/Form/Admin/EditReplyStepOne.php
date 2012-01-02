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

class Whups_Form_Admin_EditReplyStepOne extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Edit or Delete Form Replies"));
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
        $this->addVariable(
            _("Form Reply Name"), 'reply', $stype, true, false, null, $type_params);
    }

}