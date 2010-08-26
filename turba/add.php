<?php
/**
 * The Turba script to add a new entry into an address book.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

/* Setup some variables. */
$contact = null;
$vars = Horde_Variables::getDefaultVariables();
if (count($addSources) == 1) {
    $vars->set('source', key($addSources));
}
$source = $vars->get('source');
$url = $vars->get('url');

/* Exit with an error message if there are no sources to add to. */
if (!$addSources) {
    $notification->push(_("There are no writeable address books. None of the available address books are configured to allow you to add new entries to them. If you believe this is an error, please contact your system administrator."), 'horde.error');
    $url = $url
        ? Horde::url($url, true)
        : Horde::applicationUrl('index.php', true);
    $url->redirect();
}

/* A source has been selected, connect and set up the fields. */
if ($source) {
    $driver = Turba_Driver::singleton($source);
    if ($driver instanceof PEAR_Error) {
        $notification->push(sprintf(_("Failed to access the address book: %s"), $driver->getMessage()), 'horde.error');
    } else {
        /* Check permissions. */
        $max_contacts = Turba::getExtendedPermission($driver, 'max_contacts');
        if ($max_contacts !== true &&
            $max_contacts <= count($driver)) {
            try {
                $message = Horde::callHook('perms_denied', array('turba:max_contacts'));
            } catch (Horde_Exception_HookNotSet $e) {
                $message = @htmlspecialchars(sprintf(_("You are not allowed to create more than %d contacts in \"%s\"."), $max_contacts, $cfgSources[$source]['title']), ENT_COMPAT, $GLOBALS['registry']->getCharset());
            }
            $notification->push($message, 'horde.error', array('content.raw'));
            $url = $url
                ? Horde::url($url, true)
                : Horde::applicationUrl('index.php', true);
            $url->redirect();
        }

        $contact = new Turba_Object($driver);
    }
}

/* Set up the form. */
$form = new Turba_Form_AddContact($vars, $contact);

/* Validate the form. */
if ($form->validate()) {
    $form->execute();
}

$title = _("New Contact");
require TURBA_TEMPLATES . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::applicationUrl('add.php'), 'post');
require $registry->get('templates', 'horde') . '/common-footer.inc';
