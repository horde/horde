<?php
/**
 * $Id: ExtensionForm.php 502 2009-12-21 04:01:12Z bklang $
 *
 * Copyright 2005-2009 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */

class DeviceDetailsForm extends Horde_Form {

    function __construct(&$vars)
    {
        global $shout_extensions;

        if ($vars->exists('devid')) {
            $formtitle = "Edit Device";
            $devid = $vars->get('devid');
            $edit = true;
        } else {
            $formtitle = "Add Device";
            $edit = false;
        }

        parent::__construct($vars, _("$formtitle - Context: $context"));
        $this->addHidden('', 'action', 'text', true);
        if ($edit) {
            $this->addHidden('', 'devid', 'text', true);

        }
        $this->addVariable(_("Device Name"), 'name', 'text', false);
        $this->addVariable(_("Mailbox"), 'mailbox', 'int', false);
        $this->addVariable(_("CallerID"), 'callerid', 'text', false);
        $this->addVariable(_("Reset authentication token?"), 'genauthtok',
                           'boolean', false, false);//,
                          // _("If checked, the system will generate new device ID and password.  The associated device will need to be reconfigured with the new information."));

        return true;
    }

    public function execute()
    {
        global $shout_devices;

        $action = $this->_vars->get('action');
        $context = $this->_vars->get('context');
        $devid = $this->_vars->get('devid');

        // For safety, we force the device ID and password rather than rely
        // on the form to pass them around.
        if ($action == 'add') {
            // The device ID should be empty so it can be generated.
            $devid = null;
            $password = null;
        } else { // $action must be 'edit'
            $devices = $shout_devices->getDevices($context);
            if (!isset($devices[$devid])) {
                // The device requested doesn't already exist.  This can't
                // be a valid edit.
                throw new Shout_Exception(_("That device does not exist."),
                                            'horde.error');
            } else {
                $password = $devices[$devid]['password'];
            }
        }

        $details = array(
            'devid' => $devid,
            'name' => $this->_vars->get('name'),
            'mailbox' => $this->_vars->get('mailbox'),
            'callerid' => $this->_vars->get('callerid'),
            'genauthtok' => $this->_vars->get('genauthtok'),
            'password' => $password,
        );

        $shout_devices->saveDevice($context, $devid, $details);
    }

}

class DeviceDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $devid = $vars->get('devid');
        $context = $vars->get('context');

        $title = _("Delete Device %s - Context: %s");
        $title = sprintf($title, $devid, $context);
        parent::__construct($vars, $title);

        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'devid', 'text', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        global $shout_devices;
        $context = $this->_vars->get('context');
        $devid = $this->_vars->get('devid');
        $shout_devices->deleteDevice($context, $devid);
    }
}