<?php
/**
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you did
 * did not receive this file, see http://cvs.horde.org/co.php/vilma/LICENSE.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vilma
 */

class EditAliasForm extends Horde_Form {

    function EditAliasForm(&$vars)
    {
        $type = $vars->get('type');
        $editing = ($vars->get('mode') == 'edit');
        if ($editing) {
            $title = sprintf(_("Edit Alias \"%s\" for \"%s\""), $vars->get('alias_address'), $vars->get('address'));
        } else {
            $title = sprintf(_("New Alias for %s"), $vars->get('address'));
        }
        parent::Horde_Form($vars, $title);

        /* Set up the form. */
        $this->setButtons(true, true);
        $this->addHidden('', 'address', 'text', false);
        $this->addHidden('', 'mode', 'text', false);
        $this->addHidden('', 'id', 'text', false);
        if ($editing) {
            $this->addHidden('', 'alias', 'text', false);
        }
        $name = "Alias Address";
        $type = $vars->get('type');
        $this->addVariable(_($name), 'alias_address', 'email', true, false, _("The email address to add as an alias for this user.  Note that the server must be configured to receive mail for the domain contained in this address."));
    }

}
