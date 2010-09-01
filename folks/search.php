<?php
/**
 * $Id: search.php 940 2008-09-28 09:11:22Z duck $
 *
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once dirname(__FILE__) . '/lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/Search.php';

$title = _("Search");
$vars = Horde_Variables::getDefaultVariables();
$form = new Folks_Search_Form($vars, $title, 'search');

if (isset($_SESSION['folks']['last_search']) && !$form->isSubmitted()) {
    $criteria = unserialize($_SESSION['folks']['last_search']);
}
if (Horde_Util::getGet('query') && !$form->isSubmitted()) {
    $criteria = $folks_driver->getSearchCriteria(Horde_Util::getGet('query'));
    if ($criteria instanceof PEAR_Error) {
        $notification->push($criteria);
        $criteria = array();
    }
} else {
    $form->getInfo(null, $criteria);
    $_SESSION['folks']['last_search'] = serialize($criteria);
}

if (!empty($criteria)) {
    $count = $folks_driver->countUsers($criteria);
    if ($count instanceof PEAR_Error) {
        $notification->push($count);
        $count = 0;
    }

    if (($sort_by = Horde_Util::getFormData('sort_by')) !== null) {
        $criteria['sort_by'] = $sort_by;
    } else {
        $criteria['sort_by'] = $prefs->getValue('sort_by');
    }

    if (($sort_dir = Horde_Util::getFormData('sort_dir')) !== null) {
        $criteria['sort_dir'] = $sort_dir;
    } else {
        $criteria['sort_dir'] = $prefs->getValue('sort_dir');
    }

    $page = Horde_Util::getGet('page', 0);
    $perpage = $prefs->getValue('per_page');
    $users = $folks_driver->getUsers($criteria, $page * $perpage, $perpage);
    if ($users instanceof PEAR_Error) {
        $notification->push($users);
        $users = array();
    }

    $vars = Horde_Variables::getDefaultVariables();
    $pager = new Horde_Core_Ui_Pager('page',
                                $vars, array('num' => $count,
                                            'url' => 'search.php',
                                            'perpage' => $perpage));

    $pager->preserve($criteria);
    $list_url = Horde::url('search.php');

} else {
    $count = 0;
    $users = array();
}

if ($registry->isAuthenticated()) {
    $queries = $folks_driver->getSavedSearch();
    if ($queries instanceof PEAR_Error) {
        $notification->push($queries);
        $queries = array();
    }
}

Horde::addScriptFile('stripe.js', 'horde');
Horde::addScriptFile('effects.js', 'horde');
Horde::addScriptFile('redbox.js', 'horde');
Horde::addScriptFile('search.js', 'folks');

require FOLKS_TEMPLATES . '/common-header.inc';
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/list/list.php';

echo '<br />';
$form->renderActive(null, null, null, 'post');
if ($registry->isAuthenticated()) {
    require FOLKS_TEMPLATES . '/list/search.php';
}

require $registry->get('templates', 'horde') . '/common-footer.inc';
