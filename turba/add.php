<?php
/**
 * The Turba script to add a new entry into an address book.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
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
        : Horde::url('index.php', true);
    $url->redirect();
}

/* A source has been selected, connect and set up the fields. */
if ($source) {
    try {
        $driver = $injector->getInstance('Turba_Factory_Driver')->create($source);
    } catch (Turba_Exception $e) {
        $notification->push($e, 'horde.error');
        $driver = null;
    }

    if (!is_null($driver)) {
        /* Check permissions. */
        $max_contacts = Turba::getExtendedPermission($driver, 'max_contacts');
        if ($max_contacts !== true &&
            $max_contacts <= count($driver)) {
            Horde::permissionDeniedError(
                'turba',
                'max_contacts',
                sprintf(_("You are not allowed to create more than %d contacts in \"%s\"."), $max_contacts, $cfgSources[$source]['title'])
            );
            $url = $url
                ? Horde::url($url, true)
                : Horde::url('index.php', true);
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
Horde::startBuffer();
$notification->notify(array('listeners' => 'status'));
$form->renderActive(new Horde_Form_Renderer(), $vars, Horde::url('add.php'), 'post');
$formHtml = Horde::endBuffer();

$page_output->header(array(
    'title' => _("New Contact")
));
echo $formHtml;
$page_output->footer();
