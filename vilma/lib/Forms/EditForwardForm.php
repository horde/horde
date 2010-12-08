<?php
/**
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Daniel Collins <horde_dev@argentproductions.com>
 * @package Vilma
 */

class EditForwardForm extends Horde_Form {

    function EditForwardForm(&$vars)
    {
        $type = $vars->get('type');
        $editing = ($vars->get('mode') == 'edit');
        if ($editing) {
            $title = sprintf(_("Edit Forward \"%s\" for \"%s\""), $vars->get('forward_address'), $vars->get('address'));
        } else {
            $title = sprintf(_("New Forward for %s"), $vars->get('address'));
        }
        parent::Horde_Form($vars, $title);

        /* Set up the form. */
        $this->setButtons(true, true);
        $this->addHidden('', 'address', 'text', false);
        $this->addHidden('', 'mode', 'text', false);
        $this->addHidden('', 'id', 'text', false);
        if ($editing) {
            $this->addHidden('', 'forward', 'text', false);
        }
        $name = "Forward Address";
        $type = $vars->get('type');
        $this->addVariable(_($name), 'forward_address', 'email', true, false, _("The email address to add as an forward for this user.  Note that the server must be configured to receive mail for the domain contained in this address."));
    }

}
