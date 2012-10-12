<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
$beatnik = Horde_Registry::appInit('beatnik');

// Unset the current domain since we are generating a zone list
$_SESSION['beatnik']['curdomain'] = null;

// Set up categories
$cManager = new Horde_Prefs_CategoryManager();
$categories = $cManager->get();
$colors = $cManager->colors();
$fgcolors = $cManager->fgColors();

// Page results
// Check for and store the current page in the session
$page = Horde_Util::getGet('page', $_SESSION['beatnik']['curpage']);
$_SESSION['beatnik']['curpage'] = $page;

// Create the Pager UI
$pager_vars = Horde_Variables::getDefaultVariables();
$pager_vars->set('page', $page);
$perpage = $prefs->getValue('domains_perpage');
$pager = new Horde_Core_Ui_Pager('page', $pager_vars,
                            array('num' => count($beatnik->domains),
                                  'url' => 'listzones.php',
                                  'page_count' => 10,
                                  'perpage' => $perpage));

// Limit the domain list to the current page
$domains = array_slice($beatnik->domains, $page*$perpage, $perpage);

// Hide fields that the user does not want to see
$fields = Beatnik::getRecFields('soa');
foreach ($fields as $field_id => $field) {
    if ($field['type'] == 'hidden' ||
        ($field['infoset'] != 'basic' && !$_SESSION['beatnik']['expertmode'])) {
        unset($fields[$field_id]);
    }
}

// Add javascript navigation and striping
$page_output->addScriptFile('beatnik.js');
$page_output->addScriptFile('stripe.js', 'horde');

// Initialization complete.  Render the page.
Beatnik::notifyCommits();

$page_output->header();
require BEATNIK_TEMPLATES . '/menu.inc';
require BEATNIK_TEMPLATES . '/listzones/header.inc';
foreach ($domains as $domain) {
    $autourl = Horde::url('autogenerate.php')->add(array('rectype' => 'soa', 'curdomain' => $domain['zonename']));
    $deleteurl = Horde::url('delete.php')->add(array('rectype' => 'soa', 'curdomain' => $domain['zonename']));
    $viewurl = Horde::url('viewzone.php')->add('curdomain', $domain['zonename']);
    $editurl = Horde::url('editrec.php')->add(array('curdomain' => $domain['zonename'], 'id' => $domain['id'], 'rectype' => 'soa'));
    require BEATNIK_TEMPLATES . '/listzones/row.inc';
}
require BEATNIK_TEMPLATES . '/listzones/footer.inc';

$page_output->footer();
