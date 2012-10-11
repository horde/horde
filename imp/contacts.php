<?php
/**
 * Contacts selection page.
 * Usable in both traditional and dynamic views.
 *
 * URL parameters:
 *   - sa: (string) List of selected addresses.
 *   - search: (string) Search term (defaults to '' which lists everyone).
 *   - searched: (boolean) Indicates we have already searched at least once.
 *   - source: (string) The addressbook source to use.
 *   - to_only: (boolean) Are we limiting to only the 'To:' field?
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => 'horde'));

/* Sanity checking. */
if (!$registry->hasMethod('contacts/search')) {
    $e = new IMP_Exception('Addressbook not available on this system.');
    $e->logged = true;
    throw $e;
}

$vars = $injector->getInstance('Horde_Variables');

/* Get the lists of address books through the API. */
$source_list = $registry->call('contacts/sources');

/* If we self-submitted, use that source. Otherwise, choose a good
 * source. */
if (!isset($vars->source) || !isset($source_list[$vars->source])) {
    reset($source_list);
    $vars->source = key($source_list);
}

$a_list = array();
if ($vars->searched || $prefs->getValue('display_contact')) {
    $search_params = $injector->getInstance('IMP_Ui_Contacts')->getAddressbookSearchParams();
    $csearch = $registry->call('contacts/search', array($vars->get('search', ''), array(
        'fields' => $search_params['fields'],
        'returnFields' => array('email', 'name'),
        'rfc822Return' => true,
        'sources' => array($vars->source)
    )));

    foreach ($csearch as $val) {
        $a_list[] = htmlspecialchars(strval($val), ENT_QUOTES, 'UTF-8');
    }
}

/* If self-submitted, preserve the currently selected users encoded by
 * javascript to pass as value|text. */
$selected_addresses = array();
foreach (explode('|', $vars->sa) as $addr) {
    if (strlen(trim($addr))) {
        $selected_addresses[] = @htmlspecialchars($addr, ENT_QUOTES, 'UTF-8');
    }
}

/* Prepare the contacts view. */
$view = new Horde_View(array(
    'templatePath' => IMP_TEMPLATES . '/contacts'
));
$view->addHelper('FormTag');
$view->addHelper('Tag');
$view->addHelper('Text');

$view->a_list = $a_list;
$view->action = Horde::url('contacts.php')->unique();
$view->formInput = Horde_Util::formInput();
$view->sa = $selected_addresses;
$view->search = $vars->search;
$view->to_only = intval($vars->to_only);

if (count($source_list) > 1) {
    $s_list = array();
    foreach ($source_list as $key => $select) {
        $s_list[] = array(
            'label' => htmlspecialchars($select),
            'selected' => ($key == $vars->source),
            'val' => $key
        );
    }
    $view->source_list = $s_list;
} else {
    $view->source_list = key($source_list);
}

/* Display the form. */
$page_output->addScriptFile('hordecore.js', 'horde');
$page_output->addScriptFile('contacts.js');
$page_output->addInlineJsVars(array(
    'ImpContacts.text' => array(
        'closed' => _("The message being composed has been closed."),
        'select' => _("You must select an address first.")
    )
));

$page_output->topbar = $page_output->sidebar = false;

IMP::header(_("Address Book"));
echo $view->render('contacts');
$page_output->footer();
