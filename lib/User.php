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
            $limits = &$shout->getLimits($context, $extension);
            $formtitle = "Edit User";
        } else {
            $limits = &$shout->getLimits($context);
            $formtitle = "Add User";
        }

        parent::Horde_Form($vars, _("$formtitle - Context: $context"));

        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'curextension', 'text', true);
        $vars->set('curextension', $extension);
        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
        $this->addVariable(_("Full Name"), 'name', 'text', true);
        $this->addVariable(_("Extension"), 'newextension', 'int', true);
        $this->addVariable(_("E-Mail Address"), 'email', 'text', true);
        $this->addVariable(_("Pager E-Mail Address"), 'pageremail', 'text', false);
        # TODO: Integrate with To-Be-Written user manager and possibly make this
        # TODO: new user also an email account.
        $this->addVariable(_("PIN"), 'pin', 'int', true);

        $t = 1;
        while ($t <= $limits['telephonenumbersmax']) {
            $this->addVariable(_("Telephone Number $t:"), "telephone$t",
'text',
                false);
            $t++;
        }

        $this->addVariable(_("Music on Hold while transferring"), 'moh',
            'boolean', true, false);#, _("When checked, a calling user will hear music on hold while the caller is connected"));
        $this->addVariable(_("Allow Call Transfers"), 'transfer',
            'boolean', true, false);#, _("When checked, the called user will be allowed to transfer the incoming call to other extensions"));
        $this->addVariable(_("Explicit Call Acceptance"), 'eca',
            'boolean', true, false);#, _("When checked, the called user will be required to press 1 to accept the call.  Only turn this off if you really know what you're doing!"));
        $this->addVariable(_("Call Appearance"), 'callappearance',
            'radio', true, false, null, array('values' =>
                array('caller' => 'From Calling Party',
                    'self' => 'From Self',
                    'v-office' => 'From V-Office',
                )
            )
        );
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
        $vars->set('newextension', $vars->get('extension'));

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

        if (in_array('e', $userdetails['dialopts'])) {
            $vars->set('eca', true);
        } else {
            $vars->set('eca', false);
        }

        if (in_array('__CALLPRESENT:${VOFFICENUM}', $userdetails['dialopts'])) {
            $vars->set('eca', true);
            $vars->set('callappearance', 'v-office');
        } elseif (in_array('__CALLPRESENT:${CALLER}', $userdetails['dialopts'])) {
            $vars->set('eca', true);
            $vars->set('callappearance', 'caller');
        } elseif (in_array('__CALLPRESENT:${SELF}', $userdetails['dialopts'])) {
            $vars->set('eca', true);
            $vars->set('callappearance', 'self');
        }

        return true;
    }
    // }}}
}
// }}}