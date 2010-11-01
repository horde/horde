<?php
/**
 * Copyright 2010 Alkaloid Networks LLC (http://projects.alkaloid.net)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see
 * http://www.opensource.org/licenses/bsd-license.php.
 *
 * @package Shout
 */

class RecordingDetailsForm extends Horde_Form {

    function __construct(&$vars)
    {

        $formtitle = "Create Recording";

        $accountname = $vars->account;
        $title = sprintf(_("$formtitle"));
        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        //$this->addVariable(_("Description"), 'description', 'text', true);
        //$this->addVariable(_("Text"), 'text', 'text', true);
        return true;
    }

    public function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $action = $this->_vars->get('action');
        $account = $this->_vars->get('account');
        $name = $this->_vars->get('name');

        $shout->storage->addRecording($account, $name);
    }

}

class ConferenceDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $devid = $vars->get('devid');
        $account = $vars->get('account');

        $title = _("FIXME Delete Recording %s - Account: %s");
        $title = sprintf($title, $devid, $_SESSION['shout']['curaccount']['name']);
        parent::__construct($vars, $title);

        $this->addHidden('', 'account', 'text', true);
        $this->addHidden('', 'devid', 'text', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        throw new Shout_Exception('FIXME');
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $this->_vars->get('account');
        $devid = $this->_vars->get('devid');
        $shout->devices->deleteDevice($account, $devid);
    }
}