<?php
/**
 * $Id: ExtensionForm.php 502 2009-12-21 04:01:12Z bklang $
 *
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
        global $shout_extensions;

        if ($vars->exists('menu')) {
            $formtitle = _("Edit Menu");
            $menu = $vars->get('menu');
            $edit = true;
        } else {
            $formtitle = _("Add Device");
            $edit = false;
        }

        $curaccount = $_SESSION['shout']['curaccount'];
        $accountname = $_SESSION['shout']['accounts'][$curaccount];
        $title = sprintf(_("%s - Account: %s"), $formtitle, $accountname);
        parent::__construct($vars, $title);

        $this->addHidden('', 'action', 'text', true);
        
        if ($edit) {
            $this->addHidden('', 'oldname', 'text', true);
            $vars->set('oldname', $menu);
        }
        $this->addVariable(_("Menu Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'text', false);
        $this->addVariable(_("Sound File"), 'soundfile', 'text', true);

        return true;
    }

    public function execute()
    {
        global $shout;
        $account = $_SESSION['shout']['curaccount'];

        $details = array(
            'name' => $this->_vars->get('name'),
            'description' => $this->_vars->get('description'),
            'soundfile' => $this->_vars->get('description')
        );

        // FIXME: Validate soundfile

        if ($action == 'edit') {
            $details['oldname'] = $this->_vars->get('oldname');
        }

        $shout->devices->saveMenu($account, $details);
    }

}

class DeviceMenuForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $menu = $vars->get('$menu');
        $account = $vars->get('account');

        $title = _("Delete Menu %s - Account: %s");
        $title = sprintf($title, $menu, $_SESSION['shout']['accounts'][$account]);
        parent::__construct($vars, $title);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        global $shout;
        $account = $this->_vars->get('account');
        $menu = $this->_vars->get('menu');
        $shout->devices->deleteMenu($account, $menu);
    }
}