<?php
/**
 * Standard (imp) contacts display page.
 *
 * URL parameters:
 * 'formfield' - (string) Overrides the form field to fill on closing the
 *               window.
 * 'formname' - (string) Name of the calling form (defaults to 'compose').
 * 'sa' - TODO
 * 'search' - (string) Search term (defaults to '' which should list
 *            everyone).
 * 'searched' - TODO
 * 'source' - TODO
 * 'to_only' - (boolean) Are we limiting to only the 'To:' field?
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
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

if (!isset($vars->formname)) {
    $vars->formname = 'compose';
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
$template->set('formname', $vars->formname);
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
$template->set('cc', intval(!$vars->to_only));
$template->set('sa', $selected_addresses);

$js = array(
    'ImpContacts.formname = "' . $vars->formname . '"',
    'ImpContacts.to_only = ' . intval($vars->to_only)
);
if (isset($vars->formfield)) {
    $js[] = 'ImpContacts.formfield = "' . $vars->formfield . '"';
}
Horde::addInlineScript($js);

/* Display the form. */
$title = _("Address Book");
Horde::addScriptFile('contacts.js', 'imp');
require IMP_TEMPLATES . '/common-header.inc';
echo $template->fetch(IMP_TEMPLATES . '/imp/contacts/contacts.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
