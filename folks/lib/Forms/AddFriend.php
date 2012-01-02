<?php
/**
 * Copyright 2007-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 */
class Folks_AddFriend_Form extends Horde_Form {

    function __construct($vars, $title, $name)
    {
        parent::__construct($vars, $title, $name);

        $this->addVariable(_("Username"), 'user', 'text', true, false, null, array('', 20, 32));
        $this->setButtons(_("Add / Remove"));
    }
}
