<?php
/**
 * $Horde: shout/lib/Dialplan.php,v 0.1 2005/07/16 11:06:48 ben Exp $
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */
// {{{
class ExtensionDetailsForm extends Horde_Form {

    function ExtensionDetailsForm(&$vars)
    {
        global $shout;
        $context = $vars->get("context");
        $extension = $vars->get("extension");
        
        $dialplan = $shout->getDialplan($context);
        if (array_key_exists($extension, $dialplan['extensions'])) {
            $formtitle = "Edit Extension";
        } else {
            $formtitle = "Add Extension";
        }
       
        parent::Horde_Form($vars, _("$formtitle - Context: $context"));
        
        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'oldextension', 'text', true);
        $vars->set('oldextension', $extension);
        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
        $this->addVariable(_("Extension"), 'extension', 'text', true);
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
    function fillExtensionFormPriority(&$vars, $extensiondetails)
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

class ExtensionPriorityForm extends ExtensionDetailsForm {

    function ExtensionPriorityForm(&$vars)
    {
        global $shout;
        $context = $vars->get("context");
        $extension = $vars->get("extension");
        
        $dialplan = $shout->getDialplan($context);
        if (array_key_exists($extension, $dialplan['extensions'])) {
            $formtitle = "Edit Extension";
        } else {
            $formtitle = "Add Extension";
        }
        
        parent::Horde_Form($vars, _("$formtitle - Context: $context"));
        
        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'oldextension', 'text', true);
        $vars->set('oldextension', $extension);
        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
        $this->addVariable(_("Extension"), 'extension', 'text', true);
    }
}
// }}}