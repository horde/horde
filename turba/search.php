<?php
/**
 * Turba search.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$vars = Horde_Variables::getDefaultVariables();

/* Verify if the search mode variable is passed in form or is registered in
 * the session. Always use basic search by default. */
if ($vars->search_mode) {
    $session->set('turba', 'search_mode', $vars->search_mode);
}
if (!in_array($session->get('turba', 'search_mode'), array('basic', 'advanced', 'duplicate'))) {
    $session->set('turba', 'search_mode', 'basic');
}
$search_mode = $session->get('turba', 'search_mode');

/* Get the current source. */
$addressBooks = Turba::getAddressBooks();
$editableAddressBooks = Turba::getAddressBooks(Horde_Perms::EDIT & Horde_Perms::DELETE,
                                               array('require_add' => true));
if ($search_mode == 'duplicate') {
    $addressBooks = $editableAddressBooks;
}
$source = $vars->get('source', Turba::$source);
if (!isset($addressBooks[$source])) {
    $source = key($addressBooks);

    /* If there are absolutely no valid sources, abort. */
    if (!isset($addressBooks[$source])) {
        $notification->push(_("No Address Books are currently available. Searching is disabled."), 'horde.error');
        $page_output->header();
        $notification->notify(array('listeners' => 'status'));
        $page_output->footer();
        exit;
    }
}

$criteria = $vars->criteria;
$val = $vars->val;

try {
    $driver = $injector->getInstance('Turba_Factory_Driver')->create($source);
} catch (Turba_Exception $e) {
    $notification->push($e, 'horde.error');
    $driver = null;
    $map = array();
}

if ($driver) {
    $map = $driver->getCriteria();
    if ($search_mode == 'advanced') {
        $criteria = array();
        foreach (array_keys($map) as $key) {
            if ($key != '__key') {
                $value = $vars->get($key);
                if (strlen($value)) {
                    $criteria[$key] = $value;
                }
            }
        }
    }

    /* Check for updated sort criteria */
    Turba::setPreferredSortOrder($vars, $source);

    /* Only try to perform a search if we actually have search criteria. */
    if ((is_array($criteria) && count($criteria)) ||
        !empty($val) ||
        ($search_mode == 'duplicate' &&
         ($vars->search ||
          $vars->dupe ||
          count($addressBooks) == 1))) {
        if ($vars->save_vbook) {
            /* We create the vbook and redirect before we try to search
             * since we are not displaying the search results on this page
             * anyway. */
            $vname = $vars->vbook_name;
            if (empty($vname)) {
                $notification->push(_("You must provide a name for virtual address books."), 'horde.error');
                Horde::url('search.php', true)->redirect();
            }

            /* Create the vbook. */
            $params = array(
                'name' => $vname,
                'params' => serialize(array(
                    'type' => 'vbook',
                    'source' => $source,
                    'criteria' => $search_mode == 'basic' ? array($criteria => $val) : $criteria
                ))
            );

            try {
                $share = Turba::createShare(strval(new Horde_Support_Randomid()), $params);
                $vid = $share->getName();
            } catch (Horde_Share_Exception $e) {
                $notification->push(sprintf(_("There was a problem creating the virtual address book: %s"), $e->getMessage()), 'horde.error');
                Horde::url('search.php', true)->redirect();
            }

            $notification->push(sprintf(_("Successfully created virtual address book \"%s\""), $vname), 'horde.success');

            Horde::url('browse.php', true)
                ->add('source', $vid)
                ->redirect();
        }

        /* Perform a search. */
        if ($search_mode == 'duplicate') {
            try {
                $duplicates = $driver->searchDuplicates();
                $view = new Turba_View_Duplicates($duplicates, $driver, $vars->type, $vars->dupe);
                $page_output->addScriptFile('tables.js', 'horde');
            } catch (Exception $e) {
                $notification->push($e);
            }
        } else {
            try {
                if ((($search_mode == 'basic') &&
                     ($results = $driver->search(array($criteria => $val)))) ||
                    (($search_mode == 'advanced') &&
                     ($results = $driver->search($criteria)))) {
                    /* Read the columns to display from the preferences. */
                    $sources = Turba::getColumns();
                    $columns = isset($sources[$source])
                        ? $sources[$source]
                        : array();
                    $results->sort(Turba::getPreferredSortOrder());

                    $view = new Turba_View_List($results, null, $columns);
                    $view->setType('search');
                } else {
                    $notification->push(_("Failed to search the address book"), 'horde.error');
                }
            } catch (Turba_Exception $e) {
                $notification->push($e, 'horde.error');
            }
        }
    }
}

/* Build all available search criteria. */
$allCriteria = $shareSources = array();
foreach ($addressBooks as $key => $entry) {
    $allCriteria[$key] = array();
    foreach ($entry['search'] as $field) {
        $allCriteria[$key][$field] = $GLOBALS['attributes'][$field]['label'];
    }

    /* Remember vbooks and sources that are using shares. */
    $shareSources[$key] = $entry['type'] != 'vbook';
}

/* Build search mode tabs. */
$sUrl = Horde::url('search.php');
$tabs = new Horde_Core_Ui_Tabs('search_mode', $vars);
$tabs->addTab(_("Basic Search"), $sUrl, 'basic');
$tabs->addTab(_("Advanced Search"), $sUrl, 'advanced');
if (count($editableAddressBooks)) {
    $tabs->addTab(_("Duplicate Search"), $sUrl, 'duplicate');
}

/* The form header. */
$headerView = new Horde_View(array('templatePath' => TURBA_TEMPLATES . '/search'));
if (count($addressBooks) == 1) {
    $headerView->uniqueSource = key($addressBooks);
}

/* The search forms. */
$searchView = new Horde_View(array('templatePath' => TURBA_TEMPLATES . '/search'));
new Horde_View_Helper_Text($searchView);
$searchView->addressBooks = $addressBooks;
$searchView->attributes = $GLOBALS['attributes'];
$searchView->map = $map;
$searchView->blobs = $driver->getBlobs();
$searchView->source = $source;
$searchView->criteria = $criteria;
$searchView->value = $val;

/* The form footer and vbook section. */
if ($search_mode != 'duplicate') {
    $vbookView = new Horde_View(array('templatePath' => TURBA_TEMPLATES . '/search'));
    $vbookView->hasShare = $session->get('turba', 'has_share');
    $vbookView->shareSources = $shareSources;
    $vbookView->source = $source;
    $page_output->addInlineScript('$(\'vbook_name\').observe(\'keyup\', function() { $(\'save-vbook\').checked = !!$F(\'vbook_name\'); });');
}

switch ($search_mode) {
case 'basic':
    $title = _("Basic Search");
    $page_output->addScriptFile('search.js');
    $page_output->addInlineScript(array(
        '$("val").focus()'
    ), true);
    $page_output->addInlineJsVars(array(
        'TurbaSearch.criteria' => $allCriteria,
        'TurbaSearch.shareSources' => $shareSources));
    break;

case 'advanced':
    $title = _("Advanced Search");
    $page_output->addScriptFile('search.js');
    $page_output->addInlineScript(array(
        '$("name").focus()'
    ), true);
    break;

case 'duplicate':
    $title = _("Duplicate Search");
    break;
}

$page_output->addScriptFile('quickfinder.js', 'horde');
$page_output->addScriptFile('scriptaculous/effects.js', 'horde');
$page_output->addScriptFile('redbox.js', 'horde');
if (isset($view) && is_object($view)) {
    Turba::addBrowseJs();
}

$page_output->header(array(
    'title' => $title
));
$notification->notify(array('listeners' => 'status'));
echo $tabs->render($search_mode);
echo $headerView->render('header');
echo $searchView->render($search_mode);
if ($search_mode != 'duplicate') {
    echo $vbookView->render('vbook');
}
if (isset($view) && is_object($view)) {
    require TURBA_TEMPLATES . '/browse/header.inc';
    $view->display();
}
$page_output->footer();
