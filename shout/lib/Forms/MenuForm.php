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

class MenuForm extends Horde_Form {

    function __construct(&$vars)
    {
        if ($vars->exists('menu')) {
            $formtitle = _("Edit Menu");
            $menu = $vars->get('menu');
            $edit = true;
        } else {
            $formtitle = _("Add Device");
            $edit = false;
        }

        $accountname = $GLOBALS['session']->get('shout', 'curaccount_name');
        $title = sprintf(_("%s - Account: %s"), $formtitle, $accountname);
        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);

        if ($edit) {
            $this->addHidden('', 'oldname', 'text', true);
            $vars->set('oldname', $menu);
        }
        $this->addVariable(_("Menu Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'text', false);

        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $recordings = $shout->storage->getRecordings($GLOBALS['session']->get('shout', 'curaccount_code'));
        $list = array();
        foreach ($recordings as $id => $info) {
            $list[$id] = $info['filename'];
        }
        $this->addVariable(_("Recording"), 'recording_id', 'enum', true, false,
                           null, array($list));

        return true;
    }

    public function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');

        $account = $GLOBALS['session']->get('shout', 'curaccount_code');

        $details = array(
            'name' => $this->_vars->get('name'),
            'description' => $this->_vars->get('description'),
            'recording_id' => $this->_vars->get('recording_id')
        );

        if ($action == 'edit') {
            $details['oldname'] = $this->_vars->get('oldname');
        }

        $shout->devices->saveMenuInfo($account, $details);
    }

}

class DeviceMenuForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $menu = $vars->get('$menu');
        $account = $vars->get('account');

        $title = _("Delete Menu %s - Account: %s");
        $title = sprintf($title, $menu, $GLOBALS['session']->get('shout', 'curaccount_name'));
        parent::__construct($vars, $title);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        $shout = $GLOBALS['registry']->getApiInstance('shout', 'application');
        $account = $this->_vars->get('account');
        $menu = $this->_vars->get('menu');
        $shout->devices->deleteMenu($account, $menu);
    }
}
