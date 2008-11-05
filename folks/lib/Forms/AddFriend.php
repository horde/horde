<?php
/**
 * $Id: AddFriend.php 1019 2008-10-31 08:18:10Z duck $
 *
 * Copyright 2007 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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