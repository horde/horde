<?php
/**
 * $Id$
 *
 * Copyright 2005-2009 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Shout
 */

class AccountDetailsForm extends Horde_Form {

    /**
     * AccountDetailsForm constructor.
     *
     * @param mixed reference $vars
     * @return boolean
     */
    function __construct(&$vars)
    {
        $account = $_SESSION['shout']['curaccount']['code'];
        $action = $vars->get('action');
        if ($action == 'edit') {
            $formtitle = "Edit Account";
            $vars->set('oldaccount', $account);
        } else {
            $formtitle = "Add Account";
        }

        $accountname = $_SESSION['shout']['curaccount']['name'];
        $title = sprintf(_("$formtitle %s"), $accountname);
        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);
        //$this->addHidden('', 'oldaccount', 'text', false);
        $this->addVariable(_("Account Name"), 'name', 'text', true);
        $this->addVariable(_("Account Code"), 'code', 'text', true);
        $this->addVariable(_("Admin PIN"), 'adminpin', 'number', false);

        return true;
    }

    /**
     * Process this form, saving its information to the backend.
     */
    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $code = $this->_vars->get('code');
        $name = $this->_vars->get('name');
        $adminpin = $this->_vars->get('adminpin');
        if (empty($adminpin)) {
            $adminpin = rand(1000, 9999);
        }

        $shout->storage->saveAccount($code, $name, $adminpin);
    }

}

class AccountDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $extension = $vars->get('extension');
        $account = $vars->get('account');

        $title = _("Delete Extension %s - Account: %s");
        $title = sprintf($title, $extension, $_SESSION['shout']['accounts'][$account]['name']);
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
        $shout->storage->deleteAccount($account);
    }
}
