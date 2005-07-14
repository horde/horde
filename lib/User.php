<?php
/**
 * @package Whups
 */
// {{{
class UserDetailsForm extends Horde_Form {

    function UserDetailsForm(&$vars)
    {
        global $shout;

        parent::Horde_Form($vars, _("Add User"));
        
        $this->preserve($vars);
        $users = $shout->getUsers($context);
       
        $this->addVariable(_("Full Name"), 'name', text, true);
        $this->addVariable(_("Extension"), 'name', int, true);
    }
}
// }}}