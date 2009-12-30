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
        $vars->set('action', 'save');
        if ($edit) {
            $this->addHidden('', 'devid', 'text', true);

        }
        $this->addVariable(_("Device Name"), 'name', 'text', false);
        $this->addVariable(_("Mailbox"), 'mailbox', 'int', false);
        $this->addVariable(_("CallerID"), 'callerid', 'text', false);


        return true;
    }

}