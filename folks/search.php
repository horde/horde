<?php
/**
 * Copyright Obala d.o.o. (www.obala.si)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Duck <duck@obala.net>
 * @package Folks
 */

require_once __DIR__ . '/lib/base.php';
require_once FOLKS_BASE . '/lib/Forms/Search.php';

$title = _("Search");
$vars = Horde_Variables::getDefaultVariables();
$form = new Folks_Search_Form($vars, $title, 'search');

if (($last_search = $session->get('folks', 'last_search')) &&
    !$form->isSubmitted()) {
    $criteria = unserialize($last_search);
}
if (Horde_Util::getGet('query') && !$form->isSubmitted()) {
    $criteria = $folks_driver->getSearchCriteria(Horde_Util::getGet('query'));
    if ($criteria instanceof PEAR_Error) {
        $notification->push($criteria);
        $criteria = array();
    }
} else {
    $form->getInfo(null, $criteria);
    $session->set('folks', 'last_search', serialize($criteria));
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

$page_output->addScriptFile('stripe.js', 'horde');
$page_output->addScriptFile('scriptaculous/effects.js', 'horde');
$page_output->addScriptFile('redbox.js', 'horde');
$page_output->addScriptFile('search.js');

$page_output->header(array(
    'title' => $title
));
require FOLKS_TEMPLATES . '/menu.inc';
require FOLKS_TEMPLATES . '/list/list.php';

echo '<br />';
$form->renderActive(null, null, null, 'post');
if ($registry->isAuthenticated()) {
    require FOLKS_TEMPLATES . '/list/search.php';
}

$page_output->footer();
