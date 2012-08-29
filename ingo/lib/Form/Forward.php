<?php
/**
 * The form to manage forwarding rules.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Ingo
 */
class Ingo_Form_Forward extends Ingo_Form_Base
{
    public function __construct($vars, $title = '', $name = null)
    {
        parent::__construct($vars, $title, $name);

        $v = $this->addVariable(_("Keep a copy of messages in this account?"), 'keep_copy', 'boolean', false);
        $v->setHelp('forward-keepcopy');
        $v = $this->addVariable(_("Address(es) to forward to:"), 'addresses', 'longtext', false, false, null, array(5, 40));
        $v->setHelp('forward-addresses');
        $this->setButtons(_("Save"));
    }
}
