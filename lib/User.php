<?php
/**
 * @package Whups
 */
// {{{
class UserDetailsForm extends Horde_Form {

    function UserDetailsForm(&$vars)
    {
        global $shout;
        $context = $vars->get("context");

        parent::Horde_Form($vars, _("Add User - Context: $context"));

        $this->preserve($vars);
        $users = $shout->getUsers($context);

        $this->addVariable(_("Full Name"), 'name', 'text', true);
        $this->addVariable(_("Extension"), 'extension', 'int', true);
        $this->addVariable(_("E-Mail Address"), 'email', 'text', false);
        # TODO: Integrate with To-Be-Written user manager and possibly make this
        # TODO: new user also an email account.
        $this->addVariable(_("PIN"), 'pin', 'int', true);
        $this->addVariable(_("Telephone Number 1:"), 'telephone1', 'text',
            false);
        $this->addVariable(_("Telephone Number 2:"), 'telephone2', 'text',
            false);
        $this->addVariable(_("Telephone Number 3:"), 'telephone3', 'text',
            false);
        $this->addVariable(_("Telephone Number 4:"), 'telephone4', 'text',
            false);
        $this->addVariable(_("Music on Hold while transferring"), 'moh',
            'radio', true, false, null,
            array('values' => array(true => 'Yes', false => 'No')));
    }
}
// }}}