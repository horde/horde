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

class Whups_Form_Admin_AddReply extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Form Reply"));

        $this->addHidden('', 'type', 'int', true, true);
        $this->addVariable(_("Form Reply Name"), 'reply_name', 'text', true);
        $this->addVariable(_("Form Reply Text"), 'reply_text', 'longtext', true);
    }

}
