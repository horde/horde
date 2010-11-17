<?php
/**
 * Copyright 2005-2009 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Shout
 */

class ExtensionDetailsForm extends Horde_Form {

    /**
     * ExtensionDetailsForm constructor.
     *
     * @global <type> $shout->extensions
     * @param <type> $vars
     * @return <type>
     */
    function __construct(&$vars)
    {
        $action = $vars->get('action');
        if ($action == 'edit') {
            $formtitle = "Edit User";
        } else {
            $formtitle = "Add User";
        }

        $accountname = $GLOBALS['session']->get('shout', 'curaccount_name');
        $title = sprintf(_("$formtitle - Account: %s"), $accountname);
        parent::__construct($vars, $title);


        $extension = $vars->get('extension');

        $this->addHidden('', 'action', 'text', true);
        $this->addHidden('', 'oldextension', 'text', false);
        $this->addVariable(_("Full Name"), 'name', 'text', true);
        $this->addVariable(_("Extension"), 'extension', 'int', true);
        $this->addVariable(_("E-Mail Address"), 'email', 'email', true);
        //$this->addVariable(_("Pager E-Mail Address"), 'pageremail', 'email', false);
        $this->addVariable(_("PIN"), 'mailboxpin', 'int', true);

        return true;
    }

    /**
     * Process this form, saving its information to the backend.
     *
     * @param string $account  Account in which to execute this save
     * FIXME: is there a better way to get the $account and $shout->extensions?
     */
    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $extension = $this->_vars->get('extension');
        $account = $this->_vars->get('account');

        // FIXME: Input Validation (Text::??)
        $details = array(
            'name' => $this->_vars->get('name'),
            'oldextension' => $this->_vars->get('oldextension'),
            'email' => $this->_vars->get('email'),
            //'pager' => $this->_vars->get('pageremail')
            'mailboxpin' => $this->_vars->get('mailboxpin'),
            );

        $shout->extensions->saveExtension($account, $extension, $details);
    }

}

class ExtensionDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $extension = $vars->get('extension');
        $account = $vars->get('account');

        $title = _("Delete Extension %s - Account: %s");
        $title = sprintf($title, $extension, $GLOBALS['session']->get('shout', 'curaccount_name'));
        parent::__construct($vars, $title);

        $this->addHidden('', 'account', 'text', true);
        $this->addHidden('', 'extension', 'int', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $this->_vars->get('account');
        $extension = $this->_vars->get('extension');
        $shout->extensions->deleteExtension($account, $extension);
    }
}
