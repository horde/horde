<?php
/**
 * Standard (imp) contacts display page.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package IMP
 */

require_once dirname(__FILE__) . '/lib/Application.php';
new IMP_Application(array('init' => array('authentication' => 'horde')));

/* Get the lists of address books through the API. */
$source_list = $registry->call('contacts/sources');

/* If we self-submitted, use that source. Otherwise, choose a good
 * source. */
$source = Horde_Util::getFormData('source');
if (empty($source) || !isset($source_list[$source])) {
    /* We don't just pass the second argument to getFormData() because
     * we want to trap for invalid sources, not just no source. */
    reset($source_list);
    $source = key($source_list);
}

/* Get the search as submitted (defaults to '' which should list everyone). */
$search = Horde_Util::getFormData('search');

/* Get the name of the calling form (Defaults to 'compose'). */
$formname = Horde_Util::getFormData('formname', 'compose');

/* Are we limiting to only the 'To:' field? */
$to_only = Horde_Util::getFormData('to_only');

$search_params = IMP_Compose::getAddressSearchParams();
$apiargs = array(
    'addresses' => array($search),
    'addressbooks' => array($source),
    'fields' => $search_params['fields']
);

$addresses = array();
if (Horde_Util::getFormData('searched') || $prefs->getValue('display_contact')) {
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
foreach (explode('|', Horde_Util::getFormData('sa')) as $addr) {
    if (strlen(trim($addr))) {
        $selected_addresses[] = @htmlspecialchars($addr, ENT_QUOTES, Horde_Nls::getCharset());
    }
}

/* Prepare the contacts template. */
$template = new Horde_Template();
$template->setOption('gettext', true);

$template->set('action', Horde::applicationUrl('contacts.php')->add(array('uniq' => uniqid(mt_rand()))));
$template->set('formname', $formname);
$template->set('formInput', Horde_Util::formInput());
$template->set('search', htmlspecialchars($search));
if (count($source_list) > 1) {
    $template->set('multiple_source', true);
    $s_list = array();
    foreach ($source_list as $key => $select) {
        $s_list[] = array('val' => $key, 'selected' => ($key == $source), 'label' => htmlspecialchars($select));
    }
    $template->set('source_list', $s_list);
} else {
    $template->set('source_list', key($source_list));
}

$a_list = array();
foreach ($addresses as $addr) {
    if (!empty($addr['email'])) {
        if (strpos($addr['email'], ',') !== false) {
            $a_list[] = @htmlspecialchars(Horde_Mime_Address::encode($addr['name'], 'personal') . ': ' . $addr['email'] . ';', ENT_QUOTES, Horde_Nls::getCharset());
        } else {
            $mbox_host = explode('@', $addr['email']);
            if (isset($mbox_host[1])) {
                $a_list[] = @htmlspecialchars(Horde_Mime_Address::writeAddress($mbox_host[0], $mbox_host[1], $addr['name']), ENT_QUOTES, Horde_Nls::getCharset());
            }
        }
    }
}
$template->set('a_list', $a_list);
$template->set('cc', !$to_only);
$template->set('sa', $selected_addresses);

/* Display the form. */
$title = _("Address Book");
Horde::addScriptFile('contacts.js', 'imp');
require IMP_TEMPLATES . '/common-header.inc';
Horde::addInlineScript(array(
    'ImpContacts.formname = \'' . $formname . '\'',
    'ImpContacts.to_only = ' . intval($to_only),
));
echo $template->fetch(IMP_TEMPLATES . '/contacts/contacts.html');
require $registry->get('templates', 'horde') . '/common-footer.inc';
