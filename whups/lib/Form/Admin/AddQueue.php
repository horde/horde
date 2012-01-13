<?php
/**
 * This file contains all Horde_Form classes for queue administration.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Whups
 */

class Whups_Form_Admin_AddQueue extends Horde_Form
{
    public function __construct(&$vars)
    {
        parent::__construct($vars, _("Add Queue"));
        $this->appendButtons(_("Add Queue"));

        $this->addVariable(_("Queue Name"), 'name', 'text', true);
        $this->addVariable(_("Queue Description"), 'description', 'text', true);
        $this->addVariable(
            _("Queue Slug"), 'slug', 'text', false, false,
            sprintf(_("Slugs allows direct access to this queue's open tickets by visiting: %s. <br /> Slug names may contain only letters, numbers or the _ (underscore) character."),
                    Horde::url('queue/slugname', true)),
            array('/^[a-zA-Z1-9_]*$/'));
        $this->addVariable(
            _("Queue Email"), 'email', 'email', false, false,
             _("This email address will be used when sending notifications for any queue tickets."));
    }

}