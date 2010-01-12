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
        global $shout;

        $context = $_SESSION['shout']['context'];
        $action = $vars->get('action');
        if ($action == 'edit') {
            $formtitle = "Edit User";
        } else {
            $formtitle = "Add User";
        }

        parent::__construct($vars, _("$formtitle - Context: $context"));

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
     * @param string $context  Context in which to execute this save
     * FIXME: is there a better way to get the $context and $shout->extensions?
     */
    function execute()
    {
        global $shout;

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

        $shout->extensions->saveExtension($context, $extension, $details);
    }

}

class ExtensionDeleteForm extends Horde_Form
{
    function __construct(&$vars)
    {
        $extension = $vars->get('extension');
        $context = $vars->get('context');

        $title = _("Delete Extension %s - Context: %s");
        $title = sprintf($title, $extension, $context);
        parent::__construct($vars, $title);

        $this->addHidden('', 'context', 'text', true);
        $this->addHidden('', 'extension', 'int', true);
        $this->addHidden('', 'action', 'text', true);
        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        global $shout;
        $context = $this->_vars->get('context');
        $extension = $this->_vars->get('extension');
        $shout->extensions->deleteExtension($context, $extension);
    }
}
