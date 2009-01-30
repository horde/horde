<?php
/**
 * $Id: AddFriend.php 1247 2009-01-30 15:01:34Z duck $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */
class Folks_AddFriend_Form extends Horde_Form {

    function __construct($vars, $title, $name)
    {
        parent::__construct($vars, $title, $name);

        $this->addVariable(_("Username"), 'user', 'text', true, false, null, array('', 20, 32));
        $this->setButtons(_("Add / Remove"));
    }
}