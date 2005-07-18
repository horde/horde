<?php
/**
 * $Horde: shout/lib/User.php,v 0.1 2005/07/16 11:06:48 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */
// {{{
class UserDetailsForm extends Horde_Form {

    function UserDetailsForm(&$vars)
    {
        global $shout;
        $context = $vars->get("context");
        $extension = $vars->get("extension");
        
        $users = &$shout->getUsers($context);
        if (array_key_exists($extension, $users)) {
            # We must be editing an existing user
            $this->fillUserForm(&$vars, $users[$extension]);
            $formtitle = "Edit User";
        } else {
            $formtitle = "Add User";
        }
       
        parent::Horde_Form($vars, _("$formtitle - Context: $context"));
        
        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'oldextension', 'text', true);
        $vars->set('oldextension', $extension);
        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
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
        $this->addVariable(_("Allow Call Transfers"), 'transfer',
            'radio', true, false, null,
            array('values' => array(true => 'Yes', false => 'No')));
    }
    
    // {{{ fillUserForm method
    /**
     * Fill in the blanks for the UserDetailsForm
     *
     * @param object Reference to a Variables object to fill in
     *
     * @param array User details
     *
     * @return boolean True if successful, Pear::raiseError object on failure
     */
    function fillUserForm(&$vars, $userdetails)
    {
    #Array ( [dialopts] => Array ( [0] => m [1] => t ) [mailboxopts] => Array (
    #) [mailboxpin] => 1234 [name] => Ricardo Paul [phonenumbers] => Array ( )
    #[dialtimeout] => 30 [email] => ricardo.paul@v-office.biz [pageremail] => ) 
        $vars->set('name', $userdetails['name']);
        $vars->set('email', @$userdetails['email']);
        $vars->set('pin', $userdetails['mailboxpin']);
        
        $i = 1;
        foreach($userdetails['phonenumbers'] as $number) {
            $vars->set("telephone$i", $number);
            $i++;
        }
        
        if (in_array('m', $userdetails['dialopts'])) {
            $vars->set('moh', true);
        } else {
            $vars->set('moh', false);
        }
        
        if (in_array('t', $userdetails['dialopts'])) {
            $vars->set('transfer', true);
        } else {
            $vars->set('transfer', false);
        }
        
        return true;
    }
    // }}}
}
// }}}