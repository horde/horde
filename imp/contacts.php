<?php
/**
 * Contacts selection page.
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

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('imp', array('authentication' => 'horde'));

$vars = Horde_Variables::getDefaultVariables();

/* Get the lists of address books through the API. */
$source_list = $registry->call('contacts/sources');

/* If we self-submitted, use that source. Otherwise, choose a good
 * source. */
if (!isset($vars->source) || !isset($source_list[$vars->source])) {
    reset($source_list);
    $vars->source = key($source_list);
}

$search_params = IMP::getAddressbookSearchParams();
$apiargs = array(
    'addresses' => array($vars->search),
    'addressbooks' => array($vars->source),
    'fields' => $search_params['fields']
);

$addresses = array();
if ($vars->searched || $prefs->getValue('display_contact')) {
    $results = $registry->call('contacts/search', $apiargs);
    foreach ($results as $r) {
        /* The results list returns an array for each source searched. Make
         * it all one array instead. */
        $addresses = array_merge($addresses, $r);
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

/* Prepare the contacts template. */
$template = $injector->createInstance('Horde_Template');
$template->setOption('gettext', true);

$template->set('action', Horde::url('contacts.php')->unique());
$template->set('formInput', Horde_Util::formInput());
$template->set('search', htmlspecialchars($vars->search));
if (count($source_list) > 1) {
    $template->set('multiple_source', true);
    $s_list = array();
    foreach ($source_list as $key => $select) {
        $s_list[] = array('val' => $key, 'selected' => ($key == $vars->source), 'label' => htmlspecialchars($select));
    }
    $template->set('source_list', $s_list);
} else {
    $template->set('source_list', key($source_list));
}

$a_list = array();
foreach ($addresses as $addr) {
    if (!empty($addr['email'])) {
        if (strpos($addr['email'], ',') !== false) {
            $a_list[] = @htmlspecialchars(Horde_Mime_Address::encode($addr['name'], 'personal') . ': ' . $addr['email'] . ';', ENT_QUOTES, 'UTF-8');
        } else {
            $mbox_host = explode('@', $addr['email']);
            if (isset($mbox_host[1])) {
                $a_list[] = @htmlspecialchars(Horde_Mime_Address::writeAddress($mbox_host[0], $mbox_host[1], $addr['name']), ENT_QUOTES, 'UTF-8');
            }
        }
    }
}
$template->set('a_list', $a_list);
$template->set('to_only', intval($vars->to_only));
$template->set('sa', $selected_addresses);

/* Display the form. */
$title = _("Address Book");
Horde::addScriptFile('contacts.js', 'imp');
require IMP_TEMPLATES . '/common-header.inc';
echo $template->fetch(IMP_TEMPLATES . '/imp/contacts/contacts.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
