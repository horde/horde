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
        if ($vars->exists('extension')) {
            $formtitle = "Edit User";
            $extension = $vars->get('extension');
        } else {
            $formtitle = "Add User";
        }

        parent::__construct($vars, _("$formtitle - Context: $context"));
        $this->addHidden('', 'action', 'text', true);
        $vars->set('action', 'save');
        $this->addHidden('', 'extension', 'int', true);
        $vars->set('newextension', $extension);
        $this->addVariable(_("Full Name"), 'name', 'text', true);
        $this->addVariable(_("Extension"), 'newextension', 'int', true);
        $this->addVariable(_("E-Mail Address"), 'email', 'email', true);
        $this->addVariable(_("Pager E-Mail Address"), 'pageremail', 'email', false);
        $this->addVariable(_("PIN"), 'mailboxpin', 'int', true);

        return true;
    }

    /**
     * Process this form, saving its information to the backend.
     *
     * @param string $context  Context in which to execute this save
     * FIXME: is there a better way to get the $context and $shout_extensions?
     */
    function execute($context)
    {
        global $shout_extensions;

        $extension = $this->vars->get('extension');

        # FIXME: Input Validation (Text::??)
        $details = array(
            'newextension' => $vars->get('newextension'),
            'name' => $vars->get('name'),
            'mailboxpin' => $vars->get('mailboxpin'),
            'email' => $vars->get('email'),
        );

        $res = $shout_extensions->saveExtension($context, $extension, $details);
    }

}