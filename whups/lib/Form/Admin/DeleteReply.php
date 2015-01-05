<?php
/**
 * Copyright 2008-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Whups
 */

/**
 * Form to confirm reply deletions.
 *
 * @author    Jan Schneider <jan@horde.org>
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2015 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Whups
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
        $pname = $this->addVariable(
            _("Form Reply Name"), 'reply_name', 'text', false, true);
        $pname->setDefault($info['reply_name']);
        $ptext = $this->addVariable(
            _("Form Reply Text"), 'reply_text', 'text', false, true);
        $ptext->setDefault($info['reply_text']);
        $this->addVariable(
            _("Really delete this form reply?"), 'yesno', 'enum', true, false,
            null, array(array(0 => _("No"), 1 => _("Yes"))));

        $this->setButtons(array(array('class' => 'horde-delete', 'value' => _("Delete Reply"))));
    }
}
