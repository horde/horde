<?php
/**
 * Copyright 2005-2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @package Shout
 */

class DeviceDetailsForm extends Horde_Form {

    function __construct(&$vars)
    {
        if ($vars->exists('devid')) {
            $formtitle = "Edit Device";
            $devid = $vars->get('devid');
            $edit = true;
        } else {
            $formtitle = "Add Device";
            $edit = false;
        }

        $accountname = $GLOBALS['session']->get('shout', 'curaccount_name');
        $title = sprintf(_("$formtitle - Account: %s"), $accountname);
        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);
        if ($edit) {
            $this->addHidden('', 'devid', 'text', true);

        }
        $this->addVariable(_("Device Name"), 'name', 'text', true);
        $this->addVariable(_("Mailbox"), 'mailbox', 'int', false);
        $this->addVariable(_("CallerID"), 'callerid', 'text', false);
        $this->addVariable(_("Reset authentication token?"), 'genauthtok',
                           'boolean', false, false);//,
                          // _("If checked, the system will generate new device ID and password.  The associated device will need to be reconfigured with the new information."));

        return true;
    }

    public function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $action = $this->_vars->get('action');
        $account = $this->_vars->get('account');
        $devid = $this->_vars->get('devid');

        // For safety, we force the device ID and password rather than rely
        // on the form to pass them around.
        if ($action == 'add') {
            // The device ID should be empty so it can be generated.
            $devid = null;
            $password = null;
        } else { // $action must be 'edit'
            $devices = $shout->devices->getDevices($account);
            if (!isset($devices[$devid])) {
                // The device requested doesn't already exist.  This can't
                // be a valid edit.
                throw new Shout_Exception(_("That device does not exist."),
                                            'horde.error');
            } else {
                $password = $devices[$devid]['password'];
            }
        }

        $callerid = $this->_vars->get('callerid');
        $name = $this->_vars->get('name');
        $mailbox = $this->_vars->get('mailbox');

        // Default the caller id to something sane.
        if (empty($callerid)) {
            $callerid = sprintf('"%s" <%s>', $name, $mailbox);
        }

        $details = array(
            'devid' => $devid,
            'name' => $this->_vars->get('name'),
            'mailbox' => $mailbox,
            'callerid' => $callerid,
            'genauthtok' => $this->_vars->get('genauthtok'),
            'password' => $password,
        );

        $shout->devices->saveDevice($account, $devid, $details);
    }

}

class DeviceDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $devid = $vars->get('devid');
        $account = $vars->get('account');

        $title = _("Delete Device %s - Account: %s");
        $account_config = $GLOBALS['session']->get('shout', 'accounts/' . $account);
        $title = sprintf($title, $devid, $account_config['name']);
        parent::__construct($vars, $title);

        $this->addHidden('', 'account', 'text', true);
        $this->addHidden('', 'devid', 'text', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $this->_vars->get('account');
        $devid = $this->_vars->get('devid');
        $shout->devices->deleteDevice($account, $devid);
    }
}
