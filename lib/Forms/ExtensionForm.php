<?php
/**
 * $Id$
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

    var $_userdetails = false; // Store the user's details for fillUserForm

    function UserDetailsForm(&$vars)
    {
        global $shout, $notification;
        $context = $vars->get('context');
        $extension = $vars->get('extension');

        $users = &$shout->getUsers($context);
        if (is_a($users, 'PEAR_Error')) {
            $notification->push($users);
        }
        if (array_key_exists($extension, $users)) {
            # We must be editing an existing user
//             $this->fillUserForm(&$vars, $users[$extension]);
            $this->_userdetails = $users[$extension];
            $limits = &$shout->getLimits($context, $extension);
            if (is_a($limits, 'PEAR_Error')) {
                $notification->push($limits);
            }
            $formtitle = "Edit User";
            $this->addHidden('', 'uid', 'text', true);
        } else {
            $limits = &$shout->getLimits($context);
            if (is_a($limits, 'PEAR_Error')) {
                $notification->push($limits);
            }
            $formtitle = "Add User";
        }

        parent::Horde_Form($vars, _("$formtitle - Context: $context"));

        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
        $this->addVariable(_("Full Name"), 'name', 'text', true);
        $this->addVariable(_("Extension"), 'newextension', 'int', true);
        $this->addVariable(_("E-Mail Address"), 'email', 'email', true);
        $this->addVariable(_("Pager E-Mail Address"), 'pageremail', 'email', false);
        # TODO: Integrate with To-Be-Written user manager and possibly make this
        # TODO: new user also an email account.
        $this->addVariable(_("PIN"), 'mailboxpin', 'int', true);

        # FIXME: Make this work if limits don't exist.
        $t = 1;
        while ($t <= $limits['telephonenumbersmax']) {
            $this->addVariable(_("Telephone Number $t:"), "telephonenumber[$t]",
            'cellphone', false);
            $t++;
        }

        $this->addVariable(_("Music on Hold while transferring"), 'moh',
            'boolean', true, false);#, _("When checked, a calling user will hear music on hold while the caller is connected"));
        $this->addVariable(_("Allow Call Transfers"), 'transfer',
            'boolean', true, false);#, _("When checked, the called user will be allowed to transfer the incoming call to other extensions"));
        $this->addVariable(_("Explicit Call Acceptance"), 'eca',
            'boolean', true, false);#, _("When checked, the called user will be required to press 1 to accept the call.  Only turn this off if you really know what you're doing!"));
//         $this->addVariable(_("Call Appearance"), 'callappearance',
//             'radio', $vars->get('eca'), false, null, array('values' =>
//                 array('caller' => 'From Calling Party',
//                     'self' => 'From Self',
//                     'switch' => 'From V-Office',
//                 )
//             )
//         );

        return true;
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
    function fillUserForm(&$vars)
    {
    #Array ( [dialopts] => Array ( [0] => m [1] => t ) [mailboxopts] => Array (
    #) [mailboxpin] => 1234 [name] => Ricardo Paul [phonenumbers] => Array ( )
    #[dialtimeout] => 30 [email] => ricardo.paul@v-office.biz [pageremail] => )
        if (!$this->_userdetails) {
            return true;
        }
        foreach(array('name', 'email', 'pageremail', 'mailboxpin', 'uid', 'telephonenumber') as $var) {
            # FIXME This will be done the Right Way in Shout 0.7
            $vars->set($var, $this->_userdetails[$var]);
        }
//         $vars->set('name', $this->_userdetails['name']);
//         $vars->set('email', $this->_userdetails['email']);
//         $vars->set('pager', $this->_userdetails['pager']);
//         $vars->set('mailboxpin', $this->_userdetails['mailboxpin']);
//         $vars->set('uid', $this->_userdetails['uid']);
        $vars->set('newextension', $vars->get('extension'));

        $vars->set('moh', false);
        $vars->set('eca', false);
        $vars->set('transfer', false);
//         $vars->set('callappearance', 'caller');


        foreach ($this->_userdetails['dialopts'] as $opt) {
            if ($opt == 'm') {
                $vars->set('moh', true);
            }
            if ($opt == 't') {
                $vars->set('transfer', true);
            }
            if (preg_match('/^e(\(.*\))*/', $opt, $matches)) {
                # This matches 'e' and 'e(ARGS)'
                $vars->set('eca', true);
//                 if (count($matches) > 1) {
//                     # We must have found an argument
//                     switch($matches[1]) {
//                     case '(${VOFFICENUM})':
//                         $vars->set('callappearance', 'switch');
//                         break;
//
//                     case '(${CALLERANI})':
//                         $vars->set('callappearance', 'self');
//                         break;
//
//                     case '(${CALLERIDNUM})':
//                     default:
//                         $vars->set('callappearance', 'caller');
//                         break;
//
//                     }
//                 }
            }
        }
        return true;
    }
    // }}}
}
// }}}
