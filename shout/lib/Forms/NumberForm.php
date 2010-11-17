<?php
/**
 * $Id$
 *
 * Copyright 2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Shout
 */

class NumberDetailsForm extends Horde_Form {

    /**
     * AccountDetailsForm constructor.
     *
     * @param mixed reference $vars
     * @return boolean
     */
    function __construct(&$vars)
    {
        $action = $vars->get('action');
        if ($action == 'edit') {
            $title = _("Edit Number");
        } else {
            $title = _("Add Number");
        }

        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);
        //$this->addHidden('', 'oldaccount', 'text', false);
        $this->addVariable(_("Telephone Number"), 'number', 'phone', true);

        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $accounts = $shout->storage->getAccounts();
        $list = array();
        foreach ($accounts as $id => $info) {
            $list[$id] = $info['name'];
        }
        $select = $this->addVariable(_("Account Code"), 'accountcode',
                                     'enum', false, false, null, array($list));
        $action = &Horde_Form_Action::factory('reload');
        $select->setAction($action);
        $select->setOption('trackchange', true);

        $accountcode = $vars->get('accountcode');
        if (!empty($accountcode)) {
            $menus = $shout->storage->getMenus($accountcode);
            $list = array('INACTIVE' => '-- None --');
            foreach ($menus as $id => $info) {
                $list[$id] = $info['name'];
            }
            $this->addVariable(_("Menu"), 'menuName', 'enum', false,
                                         false, null, array($list));
        }
        return true;
    }

    /**
     * Process this form, saving its information to the backend.
     */
    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $number = $this->_vars->get('number');
        $accountcode = $this->_vars->get('accountcode');
        $menuName = $this->_vars->get('menuName');

        return $shout->storage->saveNumber($number, $accountcode, $menuName);
    }
}

class NumberDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        die("FIXME");
        $extension = $vars->get('extension');
        $account = $vars->get('account');

        $title = _("Delete Extension %s - Account: %s");
        $account_config = $GLOBALS['session']->get('shout', 'accounts/' . $account);
        $title = sprintf($title, $extension, $account_config['name']);
        parent::__construct($vars, $title);

        $this->addHidden('', 'account', 'text', true);
        $this->addHidden('', 'extension', 'int', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        die("FIXME");
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $this->_vars->get('account');
        $shout->storage->deleteAccount($account);
    }
}
