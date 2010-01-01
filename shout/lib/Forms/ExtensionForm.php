<?php
/**
 * $Id$
 *
 * Copyright 2005-2009 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @package Shout
 */

class ExtensionDetailsForm extends Horde_Form {

    /**
     * ExtensionDetailsForm constructor.
     * 
     * @global <type> $shout_extensions
     * @param <type> $vars
     * @return <type> 
     */
    function __construct(&$vars)
    {
        global $shout_extensions;
        $context = $_SESSION['shout']['context'];
        $action = $vars->get('action');
        if ($action == 'edit') {
            $formtitle = "Edit User";
        } else {
            $formtitle = "Add User";
        }

        $extension = $vars->get('extension');

        parent::__construct($vars, _("$formtitle - Context: $context"));
        
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
     * @param string $context  Context in which to execute this save
     * FIXME: is there a better way to get the $context and $shout_extensions?
     */
    function execute()
    {
        global $shout_extensions;

        $extension = $this->_vars->get('extension');
        $context = $this->_vars->get('context');

        // FIXME: Input Validation (Text::??)
        $details = array(
            'name' => $this->_vars->get('name'),
            'oldextension' => $this->_vars->get('oldextension'),
            'email' => $this->_vars->get('email'),
            //'pager' => $this->_vars->get('pageremail')
            'mailboxpin' => $this->_vars->get('mailboxpin'),
            );

        $shout_extensions->saveExtension($context, $extension, $details);
    }

}

class ExtensionDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        parent::__construct($vars, _("Delete Extension %s - Context: $context"));

        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'extension', 'int', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        global $shout_extensions;
        $context = $this->_vars->get('extension');
        $extension = $this->_vars->get('extension');
        $shout_extensions->deleteExtension($context, $extension);
    }
}